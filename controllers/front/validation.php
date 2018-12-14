<?php
/**
 * 2007-2017 PrestaShop
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
 *  @copyright 2007-2017 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__).'/../../classes/AddonPaymentsActions.php');

class AddonpaymentsValidationModuleFrontController extends ModuleFrontController
{

  /**
   * This class should be use by your Instant Payment
   * Notification system to validate the order remotely
   */
  public function postProcess()
  {
    if (!empty($_POST))
    {
      if ($this->module->active == false)
      {
        die;
      }

      //$merchantParameters = Tools::getValue('MERCHANT_ID');
      $signatureRecived = Tools::getValue('SHA1HASH');
      $response = Tools::getValue('RESULT');
      $AUTHCODE = Tools::getValue('AUTHCODE');
      $message = Tools::getValue('MESSAGE');
      $PASREF = Tools::getValue('PASREF');
      $TIMESTAMP = Tools::getValue('TIMESTAMP');
      $total = Tools::getValue('AMOUNT');

      /**
       * Extract Cart ID information.
       */
      $id_order = Tools::getValue('ORDER_ID');
      $orderArray = explode('-', $id_order);
      $id_cart = (int) $orderArray['0'];
      $cart = new Cart($id_cart);

      $hpp_order_id = $id_order;

      if (!Validate::isLoadedObject($cart))
      {
        PrestaShopLogger::addLog('AddonPayments::validation - Cart', 3, null, 'Cart', (int) $cart->id_cart, true);
        die('Error loading Cart');
      }
      
      $customer = new Customer((int) $cart->id_customer);
      if (!Validate::isLoadedObject($customer))
      {
        PrestaShopLogger::addLog('AddonPayments::validation - Customer', 3, null, 'Customer', (int) $cart->id_customer, true);
        die('Error loading Customer');
      }

      /**
       * Create the signature to verify auth code.
       */
      $chaine = $TIMESTAMP . '.' . $this->module->merchant_id . '.' . $id_order;
      $chaine .= '.' . $response . '.' . $message . '.' . $PASREF . '.' . $AUTHCODE;
      $sha1_temp_new = sha1($chaine);
      $signatureVerification = sha1($sha1_temp_new . '.' . $this->module->shared_secret);

      if ($signatureRecived === $signatureVerification)
      {
        
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
        {
          PrestaShopLogger::addLog('AddonPayments::validation - Cart not ok', 3, null, 'Cart', (int) $id_cart, true);
        }
        $total = $total / 100;
        $totalInFormat = number_format($total, 2, '.', '');
        $response = (int) $response;

        if ($response == 0)
        {
          $this->module->validateOrder($id_cart, Configuration::get('PS_OS_PAYMENT'), $totalInFormat, $this->module->displayName, $message, array(), (int) $cart->id_currency, false, $customer->secure_key);
        }
        else
        {
          $this->module->validateOrder($id_cart, Configuration::get('PS_OS_ERROR'), 0, $this->module->displayName, $this->module->l('error code:') . $response . $message);
        }

        /* SAVE CARD */
        if ($customer->id) {
          $tokenization = ($this->module->realvault && !(int)$customer->is_guest) ? true : false;
          if ($customer->id != null && $tokenization)
          {
            $ref_new_payer = Tools::getValue('SAVED_PAYER_REF');
            $ref_new_payment = Tools::getValue('SAVED_PMT_REF');
            //$ref_payer = Tools::getValue('PAYER_REF');
            $ref_payment = Tools::getValue('HPP_CHOSEN_PMT_REF');
            $existing_payments = false;
            $user_reference_saved = false;
            $ref_payment_found = false;
            $user_reference_saved = AddonPaymentsActions::get_adp_user_reference((int)$customer->id);
            $user_reference_saved = $user_reference_saved['user_reference'];
            if (!$user_reference_saved) {
              AddonPaymentsActions::set_adp_user_reference((int)$customer->id,$ref_new_payer);
              AddonPaymentsActions::set_adp_user_payment($ref_new_payer,$ref_new_payment);
            } else {
              $existing_payments = AddonPaymentsActions::get_adp_user_payments($user_reference_saved);
              foreach ($existing_payments as $existing_payment) {
                //var_dump($existing_payment);
                if ($existing_payment == $ref_payment) {
                  $ref_payment_found = true;
                }
              }
              if (!$ref_payment_found) {
                AddonPaymentsActions::set_adp_user_payment($user_reference_saved,$ref_new_payment);
              }
            }
          }
        }
        /* SAVE CARD */ 
        $tmp_order = new Order($this->module->currentOrder);
        $tmp_currency = new Currency((int)$tmp_order->id_currency);
        $currency_iso = $tmp_currency->iso_code;
        if ($this->module->currentOrder) {
          AddonPaymentsActions::set_adp_order_processed($this->module->currentOrder,$hpp_order_id,$PASREF,$AUTHCODE,$currency_iso);
        }
        $redirect_url = _PS_BASE_URL_ . '/index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key;
        $this->context->smarty->assign(array(
            'REDIRECT_URL' => $redirect_url
        ));
        echo "<script>window.top.location.href = '".$redirect_url."'</script>";
        die();
      } else {
        die('signature not ok');
      }
    }
  }

  public function initContent()
  {
    parent::initContent();
    $this->display_footer = false;
    $this->display_header = false;
    $this->display_column_left = false;
    $this->display_column_right = false;
    $this->setTemplate('redirect.tpl');
  }

}
