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
class AddonpaymentsPaymentModuleFrontController extends ModuleFrontController
{

  public $ssl = true;

  private function getInfosForm($account = false)
  {
    $infos = array();
    $cart = $this->context->cart;
    $id_customer = $cart->id_customer;
    $customer = new Customer((int) $id_customer);
    $infos['customer'] = $customer;
    $infos['iso_currency'] = $this->context->currency->iso_code;
    $date = new DateTime();
    $infos['timestamp'] = $date->format('YmdHis');
    $infos['order_id'] = $cart->id . '-' . $infos['timestamp'];
    $infos['settlement'] = $this->module->settlement;
    /**
     * Si tiene cuenta revisa los valores de la base de datos.
     */
    if ($account)
    {
      $sql = 'SELECT dcc_addonpayments_subaccount '
              . 'FROM `' . _DB_PREFIX_ . 'addonpayments_subaccount` '
              . 'WHERE name_addonpayments_subaccount = "' . pSQL($account) . '"';
      $result = Db::getInstance()->getRow($sql);

      $infos['settlement'] = ($result['dcc_addonpayments_subaccount'] || $this->module->settlement) ? 1 : 0;
    }

    if ($customer->id != null && !$customer->is_guest)
    {
      $sql = 'SELECT `id_addonpayments_payerref`,`refuser_addonpayments` '
              . 'FROM `' . _DB_PREFIX_ . 'addonpayments_payerref` '
              . 'WHERE `id_user_addonpayments` = ' . $id_customer;
      $payer_ref = Db::getInstance()->getRow($sql);
      $infos['payer_exists'] = (!empty($payer_ref['refuser_addonpayments'])) ? 1 : 0;
      $infos['ref_payer'] = (!empty($payer_ref['refuser_addonpayments'])) ? $payer_ref['refuser_addonpayments'] : $id_customer . $infos['timestamp'];
      $infos['id_addonpayments_payerref'] = $payer_ref['id_addonpayments_payerref'];
    }

    $billing_adresse = new Address((int) $cart->id_address_invoice);
    $infos['billing_streetumber'] = $this->parseInt($billing_adresse->address1);
    $infos['billing_co'] = Country::getIsoById($billing_adresse->id_country);
    $infos['billing_postcode'] = $this->parseInt($billing_adresse->postcode);

    $shipping_adresse = new Address((int) $cart->id_address_delivery);
    $infos['shipping_streetumber'] = $this->parseInt($shipping_adresse->address1);
    $infos['shipping_co'] = Country::getIsoById($shipping_adresse->id_country);
    $infos['shipping_postcode'] = $this->parseInt($shipping_adresse->postcode);
    $infos['cart'] = $cart;

    if ($this->module->realvault && !$customer->is_guest)
    {
      $times = $infos['timestamp'];
      $chaine = $times . '.' . $this->module->merchant_id . '.' . $infos['order_id'] . '.' . $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH));
      $chaine .= '.' . $infos['iso_currency'] . '.' . $infos['ref_payer'] . '.' . $cart->id . $infos['timestamp'];
    }
    else
    {
      $chaine = $infos['timestamp'] . '.' . $this->module->merchant_id . '.' . $infos['order_id'];
      $chaine .= '.' . $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)) . '.' . $infos['iso_currency'];
    }

    $sha1_temp_new = sha1($chaine);
    $infos['sha1_new'] = sha1($sha1_temp_new . '.' . $this->module->shared_secret);

    return $infos;
  }

  /**
   * @see FrontController::initContent()
   */
  public function initContent()
  {
    $this->display_column_left = false;
    parent::initContent();
    $this->context->smarty->assign(array('iframe_url'=>$this->renderIframe()));
    $this->setTemplate('module:addonpayments/views/templates/front/payment_iframe.tpl');
  }

  /**
   * Return only digits from a string
   * @param string $string
   * @return string
   */
  private function parseInt($string)
  {
    $string = str_replace(' ', '', $string);
    if (preg_match('/(\d+)/', $string, $array))
      return $array[1];
    else
      return 0;
  }
  public function renderIframe()
  {
      $addonpay_url = Module::getInstanceByName('addonpayments');
      $url = $addonpay_url->get_environment_url();
      $infos = $this->getInfosForm(false);
      extract($infos, EXTR_OVERWRITE);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      $id_shop = Context::getContext()->shop->id;
      $account = Db::getInstance()->getValue('SELECT `name_addonpayments_subaccount` FROM '._DB_PREFIX_.'addonpayments_subaccount WHERE id_shop = '.$id_shop);
      $required_info = array(
        'TIMESTAMP' => $timestamp,
        'MERCHANT_ID' => $this->module->merchant_id,
        'AMOUNT' => $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)),
        'ACCOUNT' => $account,
        'ORDER_ID' => $order_id,
        'CURRENCY' => $iso_currency,
        'SHA1HASH' => $sha1_new,
        'AUTO_SETTLE_FLAG' => $settlement,
        'COMMENT1' => '',
        'COMMENT2' => '',
        'SHIPPING_CODE' => $shipping_postcode,
        'SHIPPING_CO' => $shipping_co,
        'BILLING_CODE' => $billing_postcode,
        'BILLING_CO' => $billing_co,
        'CUST_NUM' => Context::getContext()->customer->id,
        'VAR_REF' => Context::getContext()->cart->id,
        'PROD_ID' => Context::getContext()->cart->id,
        'HPP_LANG' => Context::getContext()->language->iso_code,
        'HPP_CUSTOMER_EMAIL' => $customer->email,
        'HPP_CUSTOMER_FIRSTNAME' => $customer->firstname,
        'HPP_CUSTOMER_LASTNAME' => $customer->lastname,
        'MERCHANT_RESPONSE_URL' => $this->context->link->getModuleLink('addonpayments', 'validation'),
        'HPP_VERSION' => 2,
        );
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_AUTOREFERER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
      curl_setopt($ch, CURLOPT_REFERER, Context::getContext()->shop->getBaseURL(true));
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($required_info));
      $output = curl_exec($ch);
      if(curl_exec($ch) === false)
      {
          echo 'Curl error: ' . curl_error($ch);
          return 'There was an error, please, contact with the store support';
      }
      $iframe_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      curl_close($ch);
      if ($output !== false) {
          return $iframe_url;
      }
  }

  public function postProcess()
  {
    $showIframe = true;
    if($showIframe)
    {
      return;
    }
    $infos = $this->getInfosForm(false);
    extract($infos, EXTR_OVERWRITE);
    ?>
    <!DOCTYPE HTML>
    <html lang="">
      <head>
        <script languaje="javascript" >

          function OnLoadEvent() {
            document.form.submit();
          }

        </script>
      </head>
      <body onLoad="OnLoadEvent()">
        <form name="form" action="<?php echo $this->module->urltpv; ?>" method="POST" class="form">
          <input type="hidden" value="<?php echo $timestamp ?>" name="TIMESTAMP">
          <input type="hidden" value="<?php echo $this->module->merchant_id ?>" name="MERCHANT_ID">
          <input type="hidden" value="<?php echo $order_id ?>" name="ORDER_ID">
          <input type="hidden" value="<?php echo $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)) ?>" name="AMOUNT">
          <input type="hidden" value="<?php echo $iso_currency ?>" name="CURRENCY">
          <input type="hidden" value="<?php echo $sha1_new ?>" name="SHA1HASH">
          <input type="hidden" value="<?php echo $settlement ?>" name="AUTO_SETTLE_FLAG">
          <input type="hidden" name="COMMENT1" value="">
          <input type="hidden" name="COMMENT2" value="">
          <input type="hidden" value="<?php echo $shipping_postcode . '|' . $shipping_streetumber ?>" name="SHIPPING_CODE">
          <input type="hidden" value="<?php echo $shipping_co ?>" name="SHIPPING_CO">
          <input type="hidden" value="<?php echo $billing_postcode . '|' . $billing_streetumber ?>" name="BILLING_CODE">
          <input type="hidden" value="<?php echo $billing_co ?>" name="BILLING_CO">
          <input type="hidden" name="CUST_NUM" value="<?php echo Context::getContext()->customer->id; ?>">
          <input type="hidden" name="VAR_REF" value="<?php echo Context::getContext()->cart->id; ?>">
          <input type="hidden" name="PROD_ID" value="<?php echo Context::getContext()->cart->id; ?>">
          <input type="hidden" name="HPP_LANG" value="<?php echo Context::getContext()->language->iso_code; ?>">
          <input type="hidden" name="HPP_VERSION" value="2">
          <input type="hidden" name="MERCHANT_RESPONSE_URL" value="<?php echo $this->context->link->getModuleLink('addonpayments', 'validation'); ?>">
          <input type="hidden" name="CARD_PAYMENT_BUTTON" value="Pagar ahora">
          <noscript>
          <input type="submit" name="btn">
          </noscript>
        </form>
      </body>
    </html>
    <?php
    exit;
  }

}
