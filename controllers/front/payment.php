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
 *  @copyright 2007-2017 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

require_once(dirname(__FILE__).'/../../classes/AddonPaymentsActions.php');

class AddonpaymentsPaymentModuleFrontController extends ModuleFrontController
{

  public $ssl = true;

  /**
   * @see FrontController::initContent()
   */
  public function initContent()
  {
      $this->display_column_left = false;
      parent::initContent();
      $cart = $this->context->cart;
      $id_customer = (int)$cart->id_customer;
      $existing_payments = false;
      if ($id_customer) {
        $user_reference_saved = AddonPaymentsActions::get_adp_user_reference($id_customer);
        $user_reference_saved = $user_reference_saved['user_reference'];
        $existing_payments = AddonPaymentsActions::get_adp_user_payments($user_reference_saved);
      }
      if ($existing_payments) {
        $this->context->smarty->assign(array('iframe_url'=>$this->renderIframeCards()));
      } else {
        $this->context->smarty->assign(array('iframe_url'=>$this->renderIframe()));
      }
      $this->setTemplate('payment_iframe.tpl');
  }

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
          $infos['account'] = $account;
          $sql = 'SELECT dcc_addonpayments_subaccount '
                  . 'FROM `' . _DB_PREFIX_ . 'addonpayments_subaccount` '
                  . 'WHERE name_addonpayments_subaccount = "' . pSQL($account) . '"'
                  . ' AND id_shop = '.(int)$this->context->shop->id
                  . ' AND dcc_active = 1';
          $result = Db::getInstance()->getRow($sql);

          $infos['settlement'] = ($result['dcc_addonpayments_subaccount'] || $this->module->settlement) ? 1 : 0;
      } else {
          $sql = 'SELECT dcc_addonpayments_subaccount, name_addonpayments_subaccount '
                  . 'FROM `' . _DB_PREFIX_ . 'addonpayments_subaccount` '
                  . 'WHERE id_shop = '.(int)$this->context->shop->id
                  . ' AND dcc_active = 1';
          $result = Db::getInstance()->getRow($sql);
          $infos['account'] = ($result['name_addonpayments_subaccount']) ? $result['name_addonpayments_subaccount'] : false;
      }

      if ($customer->id != null && !$customer->is_guest)
      {
          $payer_ref = AddonPaymentsActions::get_adp_user_reference($customer->id);
          $infos['payer_exist'] = (!empty($payer_ref['user_reference'])) ? '1' : '0';
          $infos['ref_payer'] = (!empty($payer_ref['user_reference'])) ? $payer_ref['user_reference'] : '';
      } else {
          $infos['payer_exist'] = '0';
          $infos['ref_payer'] = '';
      }

      if ($infos['payer_exist'] && $infos['ref_payer']) {
          $payment_ref = AddonPaymentsActions::get_adp_user_payments($infos['ref_payer']);
          $sql = 'SELECT `refpayment` '
                    . 'FROM `' . _DB_PREFIX_ . 'addonpayments_paymentref` '
                    . 'WHERE `user_reference` = \'' . $infos['ref_payer'].'\'';
          $payment_ref = Db::getInstance()->getRow($sql);
      } else {
          $payment_ref = false;
      }
      
      if ($payment_ref) {
          $infos['pmt_ref'] = $payment_ref['refpayment'];
      } else {  
          $infos['pmt_ref'] = '';
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
      $infos['tokenization'] = ($this->module->realvault && !$customer->is_guest) ? '1' : '0';

      if ($this->module->realvault && !$customer->is_guest)
      {
          $chaineCommon = $infos['timestamp'] . '.' . $this->module->merchant_id . '.' . $infos['order_id'] . '.' . $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH));
          $chaine = $chaineCommon .  '.' . $infos['iso_currency'] . '.' . $infos['ref_payer'] . '.' . $infos['pmt_ref']; //we have to add it later because the user can choose more payments
          $chaineTokenised = $chaineCommon . '.' . $infos['iso_currency'] . '.' . $infos['ref_payer'] . '.';
      }
      else
      {
          $chaine = $infos['timestamp'] . '.' . $this->module->merchant_id . '.' . $infos['order_id'];
          $chaine .= '.' . $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)) . '.' . $infos['iso_currency'];
      }
      
        $sha1_temp_new = sha1($chaine);
        $infos['sha1_new'] = sha1($sha1_temp_new . '.' . $this->module->shared_secret);
      if ($this->module->realvault && !$customer->is_guest) {
          $sha1_temp_newTokenized = sha1($chaineTokenised);
          $infos['sha1_new_tokenized'] = sha1($sha1_temp_newTokenized . '.' . $this->module->shared_secret);
      }

      return $infos;
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
      $url = AddonPaymentsActions::get_environment_url();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      $cart = $this->context->cart;
      $id_customer = $cart->id_customer;
      $customer = new Customer((int) $id_customer);
      $infos = $this->getInfosForm(false);
      extract($infos, EXTR_OVERWRITE);
      $lang_id = (int) Context::getContext()->language->id;
      $config = Configuration::getMultiple(array(
                'ADDONPAYMENTS_URLTPV',
                'ADDONPAYMENTS_MERCHANT_ID',
                'ADDONPAYMENTS_SHARED_SECRET',
                'ADDONPAYMENTS_REDIRECT_SETTLEMENT',
                'ADDONPAYMENTS_REDIRECT_SUBACCOUNT',
                'ADDONPAYMENTS_REDIRECT_REALVAULT',
                'ADDONPAYMENTS_OFFER_SAVE_CARD',
                'ADDONPAYMENTS_REDIRECT_CVN',
                'ADDONPAYMENTS_CARD_PAYMENT_BUTTON_'.$lang_id,
                'ADDONPAYMENTS_REDIRECT_LIABILITY')
      );
      $offer_save_card = '0';
      if ($config['ADDONPAYMENTS_OFFER_SAVE_CARD']) {
          $offer_save_card = '1';
      }
      if ($config['ADDONPAYMENTS_CARD_PAYMENT_BUTTON_'.$lang_id] != null) {
          $current_lang_button_text = $config['ADDONPAYMENTS_CARD_PAYMENT_BUTTON_'.$lang_id];
      } else {
          $current_lang_button_text = '';
      }
      
      if ($tokenization) {
          $required_info = array(
          'TIMESTAMP' => $timestamp,
          'MERCHANT_ID' => $this->module->merchant_id,
          'ACCOUNT' => $account,
          'ORDER_ID' => $order_id,
          'AMOUNT' => $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)),
          'CURRENCY' => $iso_currency,
          'SHA1HASH' => $sha1_new,
          'AUTO_SETTLE_FLAG' => $this->module->settlement,
          'HPP_VERSION' => 2,
          'CARD_STORAGE_ENABLE' => $tokenization,
          'OFFER_SAVE_CARD' => $offer_save_card,
          'PAYER_EXIST' => $payer_exist,
          'PAYER_REF' => $ref_payer,
          'PMT_REF' => $pmt_ref, //saved card
          'HPP_LANG' => Context::getContext()->language->iso_code, //saved card
          'MERCHANT_RESPONSE_URL' => $this->context->link->getModuleLink('addonpayments', 'validation'),
          );
      } else {
          $required_info = array(
          'TIMESTAMP' => $timestamp,
          'MERCHANT_ID' => $this->module->merchant_id,
          'ACCOUNT' => $account,
          'ORDER_ID' => $order_id,
          'AMOUNT' => $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)),
          'CURRENCY' => $iso_currency,
          'SHA1HASH' => $sha1_new,
          'AUTO_SETTLE_FLAG' => $this->module->settlement,
          'HPP_VERSION' => 2,
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
          );
      }
      if ($current_lang_button_text != '') {
          $required_info['CARD_PAYMENT_BUTTON'] = $current_lang_button_text;
      }
      $required_info['HPP_POST_DIMENSIONS'] = (Configuration::get('PS_SSL_ENABLED'))?Tools::getShopDomainSsl(true, true).__PS_BASE_URI__:Tools::getShopDomain(true, true).__PS_BASE_URI__;
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

  public function renderIframeCards()
  {
      $url = AddonPaymentsActions::get_environment_url();
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      $cart = $this->context->cart;
      $id_customer = $cart->id_customer;
      $customer = new Customer((int) $id_customer);
      $infos = $this->getInfosForm(false);
      extract($infos, EXTR_OVERWRITE);
      $lang_id = (int) Context::getContext()->language->id;
      $config = Configuration::getMultiple(array(
                'ADDONPAYMENTS_URLTPV',
                'ADDONPAYMENTS_MERCHANT_ID',
                'ADDONPAYMENTS_SHARED_SECRET',
                'ADDONPAYMENTS_REDIRECT_SETTLEMENT',
                'ADDONPAYMENTS_REDIRECT_SUBACCOUNT',
                'ADDONPAYMENTS_REDIRECT_REALVAULT',
                'ADDONPAYMENTS_OFFER_SAVE_CARD',
                'ADDONPAYMENTS_REDIRECT_CVN',
                'ADDONPAYMENTS_CARD_PAYMENT_BUTTON_'.$lang_id,
                'ADDONPAYMENTS_REDIRECT_LIABILITY')
      );
      $offer_save_card = '0';
      if ($config['ADDONPAYMENTS_OFFER_SAVE_CARD']) {
          $offer_save_card = '1';
      }
      if ($config['ADDONPAYMENTS_CARD_PAYMENT_BUTTON_'.$lang_id] != null) {
          $current_lang_button_text = $config['ADDONPAYMENTS_CARD_PAYMENT_BUTTON_'.$lang_id];
      } else {
          $current_lang_button_text = '';
      }
      
      if ($tokenization) {
          $required_info = array(
          'TIMESTAMP' => $timestamp,
          'MERCHANT_ID' => $this->module->merchant_id,
          'ACCOUNT' => $account,
          'ORDER_ID' => $order_id,
          'AMOUNT' => $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)),
          'CURRENCY' => $iso_currency,
          'SHA1HASH' => $sha1_new_tokenized,
          'AUTO_SETTLE_FLAG' => $this->module->settlement,
          'HPP_VERSION' => 2,
          'OFFER_SAVE_CARD' => $offer_save_card,
          'PAYER_EXIST' => $payer_exist,
          'PAYER_REF' => $ref_payer,
          'HPP_SELECT_STORED_CARD' => $ref_payer,
          'HPP_LANG' => Context::getContext()->language->iso_code, //saved card
          'MERCHANT_RESPONSE_URL' => $this->context->link->getModuleLink('addonpayments', 'validation'),
          );
      } else {
          $required_info = array(
          'TIMESTAMP' => $timestamp,
          'MERCHANT_ID' => $this->module->merchant_id,
          'ACCOUNT' => $account,
          'ORDER_ID' => $order_id,
          'AMOUNT' => $this->module->getAmountFormat($cart->getOrderTotal(true, Cart::BOTH)),
          'CURRENCY' => $iso_currency,
          'SHA1HASH' => $sha1_new,
          'AUTO_SETTLE_FLAG' => $this->module->settlement,
          'HPP_VERSION' => 2,
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
          );
      }
      if ($current_lang_button_text != '') {
          $required_info['CARD_PAYMENT_BUTTON'] = $current_lang_button_text;
      }
      $required_info['HPP_POST_DIMENSIONS'] = (Configuration::get('PS_SSL_ENABLED'))?Tools::getShopDomainSsl(true, true).__PS_BASE_URI__:Tools::getShopDomain(true, true).__PS_BASE_URI__;
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
}
