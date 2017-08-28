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
class AddonpaymentsValidationModuleFrontController extends ModuleFrontController
{

  /**
   * This class should be use by your Instant Payment
   * Notification system to validate the order remotely
   */
  public function postProcess()
  {
    //Note: This resolves as true even if all $_POST values are empty strings
    if (!empty($_POST))
    {
      /**
       * If the module is not active anymore, no need to process anything.
       */
      if ($this->module->active == false)
      {
        die;
      }

      $merchantParameters = Tools::getValue('MERCHANT_ID');
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
      if (!Validate::isLoadedObject($cart))
      {
        PrestaShopLogger::addLog('AddonPayments::validation - Cart', 3, null, 'Cart', (int) $cart->id_cart, true);
        die('Error loading Cart');
      }
      $currency = new Currency($cart->id_currency);

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

        $this->context->smarty->assign(array(
            'REDIRECT_URL' => _PS_BASE_URL_ . '/index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key
        ));
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
    $this->setTemplate('module:addonpayments/views/templates/front/redirect.tpl');
  }

}
