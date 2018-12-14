<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
class AddonPaymentsActions
{
    public static function get_environment_url()
    {
        return Configuration::get('ADDONPAYMENTS_URLTPV') ? 'https://hpp.addonpayments.com/pay' : 'https://hpp.sandbox.addonpayments.com/pay';
    }
    public static function get_environment_url_rebate()
    {
        $url = '';
        if (Configuration::get('ADDONPAYMENTS_URLTPV')) {
            $url = "https://remote.sandbox.addonpayments.com/remote";
        } else {
            $url = "https://remote.sandbox.addonpayments.com/remote";
        }
        return $url;
    }
    public static function get_adp_user_reference($id_customer) {
        $tmp_actions = new AddonPaymentsActions();
        return $tmp_actions->get_user_reference($id_customer);
    }
    public static function get_adp_user_payments($ref_payer) {
        $tmp_actions = new AddonPaymentsActions();
        return $tmp_actions->get_user_payments($ref_payer);
    }
    public static function set_adp_user_reference ($id_customer,$ref_payer) {
        if (!$ref_payer || empty($ref_payer) || $ref_payer == '') {
            return false;
        }
        $tmp_actions = new AddonPaymentsActions();
        return $tmp_actions->set_user_reference($id_customer,$ref_payer);
    }
    public static function set_adp_user_payment ($ref_payer,$ref_payment) {
        if (!$ref_payer || empty($ref_payer) || $ref_payer == '') {
            return false;
        }
        $tmp_actions = new AddonPaymentsActions();
        return $tmp_actions->set_user_payments($ref_payer,$ref_payment);
    }
    public static function set_adp_order_processed($id_order,$hpp_order_id,$PASREF,$AUTHCODE,$currency_iso) {
        $tmp_actions = new AddonPaymentsActions();
        return $tmp_actions->set_order_processed($id_order,$hpp_order_id,$PASREF,$AUTHCODE,$currency_iso);    
    }
    public static function set_addonpayments_first() {
        $id_hook = Hook::getIdByName('displayPayment');
        $addonpayments = Module::getInstanceByName('addonpayments');
        return $addonpayments->updatePosition($id_hook, 0, 1);
    }

    public static function set_order_processed_ok($id_order)
    {
        $sql = 'UPDATE  `' . _DB_PREFIX_ . 'addonpayments_order_processed` SET validated = 1 WHERE id_order = '.$id_order;
        if (Db::getInstance()->execute($sql))
          return true;
        return false;
    }

    public static function existOrder($id_order) {
        $result = false;
        $sql = 'SELECT * FROM  `' . _DB_PREFIX_ . 'addonpayments_order_processed` WHERE `id_order` = '.$id_order;
        $sql = Db::getInstance()->getRow($sql);
        if (!count($sql)) {
            return $result;
        } else {
          if ((int)$sql['rebated'] == 1) {
              $result = 'r';
          } else {
              $result = 'e';
          }
        }     
        return $result;
    }

    public static function rebateOrder($id_order, $rebate_import) {
        $rebate_result = array('error' => false, 'message' => '');
          //we make an update to rebate it and return the result true if all went ok
          //addonpaymentsrebate process
        $sql1 = 'SELECT * FROM  `' . _DB_PREFIX_ . 'addonpayments_order_processed` WHERE `id_order` = '.$id_order;
        $sql1 = Db::getInstance()->getRow($sql1);
          //PrestaShopLogger::addLog('edrftgyhujiko2', 1, null, 'Transaction Id Cart', 10, true);
        if (!count($sql1)) {
          return false;
        }
        $presta_order = new Order ((int)$sql1['id_order']);
        $max_permitted = round($presta_order->total_paid , 2) * 100;
        $amount = round($rebate_import , 2) * 100;
        if ($amount <= 0 || $amount > $max_permitted) {
            return;
        }

        $url = self::get_environment_url_rebate();
        $date = new DateTime();
        $timestamp = $date->format('YmdHis');
        $sql = 'SELECT dcc_addonpayments_subaccount, name_addonpayments_subaccount '
        . 'FROM `' . _DB_PREFIX_ . 'addonpayments_subaccount` '
        . 'WHERE id_shop = '.(int)Context::getContext()->shop->id;
        $result = Db::getInstance()->getRow($sql);
        $account = ($result['name_addonpayments_subaccount']) ? $result['name_addonpayments_subaccount'] : false;
        $addonpayments = Module::getInstanceByName('addonpayments');
        $merchantid = $addonpayments->merchant_id;//'micomercionline';//$this->module->merchant_id;//$config['ADDONPAYMENTS_MERCHANT_ID'];
        $shared_secret = $addonpayments->shared_secret;
        $rebate = Configuration::get('ADDONPAYMENTS_REBATE_PASSWORD');//'rebate'
        if (!$merchantid && !$shared_secret && !$rebate) { //in case we have multistore and we want to do the rebate from the All stores view, we will get a null merchant id, so we will get the store 1 by default
            $merchantid = Configuration::get('ADDONPAYMENTS_MERCHANT_ID',null,1,1);
            $shared_secret = Configuration::get('ADDONPAYMENTS_SHARED_SECRET',null,1,1);
            $rebate = Configuration::get('ADDONPAYMENTS_REBATE_PASSWORD',null,1,1);
        }
        $orderid = $sql1['HPP_ORDER_ID'];
        $currency = $sql1['CURRENCY'];
        $pasref = $sql1['HPP_PAS_REF'];
        $authcode = $sql1['HPP_AUTHCODE'];
        $chaine = $timestamp.'.'.$merchantid.'.'.$orderid.'.'.$amount.'.'.$currency.'.';
        $sha1hash = sha1($chaine);
        $sha1hash = sha1($sha1hash.'.'.$shared_secret);
        if (empty($rebate) || $rebate == '') {
            $rebate_values = Configuration::getMultiShopValues('ADDONPAYMENTS_REBATE_PASSWORD');
          if (count($rebate_values)) {
              $rebate = reset($rebate_values); //this gives the first element
          }
        }
        if (empty($rebate) || $rebate == '') {
            $rebate_result['error'] = true;
            $rebate_result['message'] = $addonpayments->messages['rebate_password_empty'];
            return $rebate_result;
        }
        $xml_rebate = "<?xml version='1.0' encoding='UTF-8'?>
        <request type='rebate' timestamp='".$timestamp."'>
          <merchantid>".$merchantid."</merchantid>
          <account>".$account."</account>
          <orderid>".$orderid."</orderid>
          <amount currency='".$currency."'>".$amount."</amount>
          <pasref>".$pasref."</pasref>
          <authcode>".$authcode."</authcode>
          <refundhash>".sha1($rebate)."</refundhash>".
          "<sha1hash>".$sha1hash."</sha1hash>
        </request>";
        $headers = array(
          "Content-type: text/xml",
          "Content-length: " . Tools::strlen($xml_rebate),
          "Connection: close",
          );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_rebate);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $error = curl_error($ch); 
        curl_close($ch);
        if (!$error) {
            $sql2 = 'UPDATE  `' . _DB_PREFIX_ . 'addonpayments_order_processed` SET rebated = 1 WHERE id_order = '.$id_order;
            $order = new Order((int)$id_order);
            $current_order_state = $order->getCurrentOrderState();
            $refund_state = new OrderState(Configuration::get('PS_OS_REFUND'));
          if ($current_order_state->id != $refund_state->id) {
              $history = new OrderHistory();
              $history->id_order = $order->id;
              $use_existings_payment = false;
            if (!$order->hasInvoice()) {
                $use_existings_payment = true;
            }
            $history->changeIdOrderState((int) $refund_state->id, $order, $use_existings_payment);
            $history->addWithemail(true);
          }
          
          if (Db::getInstance()->execute($sql2)) {
              $rebate_result['error'] = false;
              $rebate_result['message'] = '';
          } else {
              $rebate_result['error'] = true;
              $rebate_result['message'] = $addonpayments->messages['rebate_connection_error'];
          }
        } else {
            $rebate_result['error'] = true;
            $rebate_result['message'] = $addonpayments->messages['rebate_connection_error'];
        }
        return $rebate_result;
    }
    private function get_user_reference($id_customer) {
        $sql = 'SELECT `user_reference` '
        . 'FROM `' . _DB_PREFIX_ . 'addonpayments_payerref` '
        . 'WHERE `id_customer` = ' . (int)$id_customer;
        $ref_payer = Db::getInstance()->getRow($sql);
        return $ref_payer;
    }
    private function get_user_payments($ref_payer) {
        $sql = 'SELECT `refpayment` '
        . 'FROM `' . _DB_PREFIX_ . 'addonpayments_paymentref` '
        . 'WHERE `user_reference` = \'' . $ref_payer .'\'';
        $payment_ref = Db::getInstance()->getRow($sql);
        return $payment_ref;
    }
    private function set_user_reference($id_customer,$ref_payer) {
        $sql = 'INSERT INTO  `' . _DB_PREFIX_ . 'addonpayments_payerref` (`id_customer`,`user_reference`,`date_add`) VALUES ('.$id_customer.',\''.$ref_payer.'\',NOW())';
        if (Db::getInstance()->execute($sql))
          return true;
        return false;
    }
    private function set_user_payments($ref_payer,$ref_payment) {
        $sql = 'INSERT INTO  `' . _DB_PREFIX_ . 'addonpayments_paymentref` (`user_reference`,`refpayment`,`type_card_addonpayments`,`date_add`) VALUES (\''.$ref_payer.'\',\''.$ref_payment.'\',\'\',NOW())';
        if (Db::getInstance()->execute($sql))
          return true;
        return false;
    }
    private function set_order_processed($id_order,$hpp_order_id,$PASREF,$AUTHCODE,$currency_iso) {
        $sql = 'INSERT INTO  `' . _DB_PREFIX_ . 'addonpayments_order_processed` (`id_order`,`HPP_ORDER_ID`,`HPP_PAS_REF`,`HPP_AUTHCODE`,`CURRENCY`,rebated) VALUES ('.$id_order.',\''.$hpp_order_id.'\',\''.$PASREF.'\',\''.$AUTHCODE.'\',\''.$currency_iso.'\',0)';
        if (Db::getInstance()->execute($sql))
          return true;
        return false;
    }
}
