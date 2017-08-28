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
if (!defined('_PS_VERSION_'))
{
  exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Addonpayments extends PaymentModule
{

  protected $config_form = false;
  public $env;
  public $urltpv;
  public $merchant_id;
  public $shared_secret;
  public $settlement;
  public $realvault;
  public $cvn;
  public $bout_suppr;
  public $liability;

  public function __construct()
  {
    $this->name = 'addonpayments';
    $this->tab = 'payments_gateways';
    $this->version = '1.1.0';
    $this->author = 'eComm360 S.L.';
    $this->need_instance = 0;
    $this->controllers = array('payment', 'validation');

    /**
     * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
     */
    $this->bootstrap = true;
    $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

    $config = Configuration::getMultiple(array(
                'ADDONPAYMENTS_URLTPV',
                'ADDONPAYMENTS_MERCHANT_ID',
                'ADDONPAYMENTS_SHARED_SECRET',
                'ADDONPAYMENTS_REDIRECT_SETTLEMENT',
                'ADDONPAYMENTS_REDIRECT_SUBACCOUNT',
                'ADDONPAYMENTS_REDIRECT_REALVAULT',
                'ADDONPAYMENTS_REDIRECT_CVN',
                'ADDONPAYMENTS_REDIRECT_LIABILITY')
    );

    $this->env = $config['ADDONPAYMENTS_URLTPV'];
    switch ($this->env)
    {
      case 0: //Test
        $this->urltpv = 'https://hpp.sandbox.addonpayments.com/pay';
        break;
      case 1: //Real
        $this->urltpv = 'https://hpp.addonpayments.com/pay';
        break;
    }

    if (isset($config['ADDONPAYMENTS_MERCHANT_ID']))
    {
      $this->merchant_id = $config['ADDONPAYMENTS_MERCHANT_ID'];
    }
    if (isset($config['ADDONPAYMENTS_SHARED_SECRET']))
    {
      $this->shared_secret = $config['ADDONPAYMENTS_SHARED_SECRET'];
    }
    if (isset($config['ADDONPAYMENTS_REDIRECT_SETTLEMENT']))
    {
      $this->settlement = $config['ADDONPAYMENTS_REDIRECT_SETTLEMENT'];
    }
    if (isset($config['ADDONPAYMENTS_REDIRECT_REALVAULT']))
    {
      $this->realvault = $config['ADDONPAYMENTS_REDIRECT_REALVAULT'];
    }
    if (isset($config['ADDONPAYMENTS_REDIRECT_CVN']))
    {
      $this->cvn = $config['ADDONPAYMENTS_REDIRECT_CVN'];
    }
    if (isset($config['ADDONPAYMENTS_REDIRECT_LIABILITY']))
    {
      $this->liability = $config['ADDONPAYMENTS_REDIRECT_LIABILITY'];
    }

    parent::__construct();

    $this->displayName = $this->trans('AddonPayments Official', array(), 'Modules.Addonpayments.Admin');
    $this->description = $this->trans('Este módulo le permite aceptar pagos a través de la forma de pago Addon Payments.', array(), 'Modules.Addonpayments.Admin');

    $this->confirmUninstall = $this->trans('Are you sure you want to uninstall my module?.', array(), 'Modules.Addonpayments.Admin');

    if (!isset($this->merchant_id) ||
            empty($this->shared_secret) ||
            !isset($this->settlement) ||
            !isset($this->realvault) ||
            !isset($this->cvn) ||
            !isset($this->liability)
    )
      $this->warning = $this->trans('Realex Payment details must be configured before using this module.', array(), 'Modules.Addonpayments.Admin');

    if (!$this->getTableAccount())
    {
      $this->warning = $this->trans('You have to configure at least one subaccount', array(), 'Modules.Addonpayments.Admin');
    }
    if (!count(Currency::checkPaymentCurrencies($this->id)))
    {
      $this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.Addonpayments.Admin');
    }
  }

  /**
   * Don't forget to create update methods if needed:
   * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
   */
  public function install()
  {
    if (extension_loaded('curl') == false)
    {
      $this->_errors[] = $this->trans('You have to enable the cURL extension on your server to install this module', array(), 'Modules.Addonpayments.Admin');
      return false;
    }

    if (!Configuration::get('PS_REWRITING_SETTINGS'))
    {
      $this->_errors[] = $this->trans('URL Rewriting must be enabled before using this module.', array(), 'Modules.Addonpayments.Admin');
      return false;
    }

    include(dirname(__FILE__) . '/sql/install.php');

    return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            Configuration::updateValue('ADDONPAYMENTS_REDIRECT_SETTLEMENT', true) &&
            Configuration::updateValue('ADDONPAYMENTS_REDIRECT_REALVAULT', '0') &&
            Configuration::updateValue('ADDONPAYMENTS_REDIRECT_CVN', '0') &&
            Configuration::updateValue('ADDONPAYMENTS_REDIRECT_LIABILITY', '0');
  }

  public function uninstall()
  {
    include(dirname(__FILE__) . '/sql/uninstall.php');

    return parent::uninstall() &&
            Configuration::deleteByName('ADDONPAYMENTS_MERCHANT_ID') &&
            Configuration::deleteByName('ADDONPAYMENTS_SHARED_SECRET') &&
            Configuration::deleteByName('ADDONPAYMENTS_REDIRECT_SETTLEMENT') &&
            Configuration::deleteByName('ADDONPAYMENTS_REDIRECT_SUBACCOUNT') &&
            Configuration::deleteByName('ADDONPAYMENTS_REDIRECT_REALVAULT') &&
            Configuration::deleteByName('ADDONPAYMENTS_REDIRECT_CVN') &&
            Configuration::deleteByName('ADDONPAYMENTS_REDIRECT_LIABILITY');
  }

  /**
   * Load the configuration form
   */
  public function getContent()
  {
    /**
     * If values have been submitted in the form, process.
     */
    if (((bool) Tools::isSubmit('submitAddonpaymentsModule')) == true)
    {
      $this->postProcess();
    }
    if (((bool) Tools::isSubmit('submitListAddonpaymentsModule')) == true)
    {
      $this->postProcessList();
    }
    if (((bool) Tools::isSubmit('submitUpdateSubaccount')) == true)
    {
      $this->postProcessListUpdate();
    }

    if (((bool) Tools::isSubmit('deleteaddonpayments_subaccount')) == true)
    {
      $this->postProcessListDelete();
    }

    $this->context->smarty->assign('module_dir', $this->_path);

    $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

    $output .= $this->renderForm();


    if (count($this->getTableAccount()))
    {
      $output .= $this->renderList();
    }
    $output .= $this->renderFormList();

    return $output;
  }

  /**
   * Create the form that will be displayed in the configuration of your module.
   */
  protected function renderForm()
  {
    $helper = new HelperForm();

    $helper->show_toolbar = false;
    $helper->table = $this->table;
    $helper->module = $this;
    $helper->default_form_language = $this->context->language->id;
    $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

    $helper->identifier = $this->identifier;
    $helper->submit_action = 'submitAddonpaymentsModule';
    $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');

    $helper->tpl_vars = array(
        'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
        'languages' => $this->context->controller->getLanguages(),
        'id_language' => $this->context->language->id,
    );

    return $helper->generateForm(array($this->getConfigForm()));
  }

  /**
   * Create the structure of your form.
   */
  protected function getConfigForm()
  {
    return array(
        'form' => array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Environment'),
                    'name' => 'ADDONPAYMENTS_URLTPV',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Enter your merchand ID'),
                    'name' => 'ADDONPAYMENTS_MERCHANT_ID',
                    'label' => $this->l('Merchand ID'),
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ADDONPAYMENTS_SHARED_SECRET',
                    'label' => $this->l('Shared secret key'),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Settlement'),
                    'name' => 'ADDONPAYMENTS_REDIRECT_SETTLEMENT',
                    'is_bool' => true,
                    'desc' => $this->l('If you are using DCC the settlement type will be automatically set to Auto'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('RealVault'),
                    'name' => 'ADDONPAYMENTS_REDIRECT_REALVAULT',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Request Security Code'),
                    'name' => 'ADDONPAYMENTS_REDIRECT_CVN',
                    'is_bool' => true,
                    'desc' => $this->l('Request Security Code on tokenised transactions.'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Require Liability Shift'),
                    'name' => 'ADDONPAYMENTS_REDIRECT_LIABILITY',
                    'is_bool' => true,
                    'desc' => $this->l('Require Liability Shift on 3DSecure transactions'),
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        ),
    );
  }

  /**
   * Set values for the inputs.
   */
  protected function getConfigFormValues()
  {
    return array(
        'ADDONPAYMENTS_URLTPV' => Configuration::get('ADDONPAYMENTS_URLTPV'),
        'ADDONPAYMENTS_MERCHANT_ID' => Configuration::get('ADDONPAYMENTS_MERCHANT_ID'),
        'ADDONPAYMENTS_SHARED_SECRET' => Configuration::get('ADDONPAYMENTS_SHARED_SECRET', null),
        'ADDONPAYMENTS_REDIRECT_SETTLEMENT' => Configuration::get('ADDONPAYMENTS_REDIRECT_SETTLEMENT'),
        'ADDONPAYMENTS_REDIRECT_REALVAULT' => Configuration::get('ADDONPAYMENTS_REDIRECT_REALVAULT'),
        'ADDONPAYMENTS_REDIRECT_CVN' => Configuration::get('ADDONPAYMENTS_REDIRECT_CVN'),
        'ADDONPAYMENTS_REDIRECT_LIABILITY' => Configuration::get('ADDONPAYMENTS_REDIRECT_LIABILITY'),
    );
  }

  /**
   * Save form data.
   */
  protected function postProcess()
  {
    $form_values = $this->getConfigFormValues();

    foreach (array_keys($form_values) as $key)
    {
      Configuration::updateValue($key, Tools::getValue($key));
    }
  }

  public function renderList()
  {
    $shops = Shop::getContextListShopID();
    $links = $this->getListTableAccount();

    $fields_list = array(
        'name_addonpayments_subaccount' => array(
            'title' => $this->l('SubAccount name'),
            'type' => 'text',
        ),
        'cards' => array(
            'title' => $this->l('Cards Selected'),
            'type' => 'text',
        ),
        'threeds_addonpayments_subaccount' => array(
            'title' => $this->l('3DSecure'),
            'type' => 'bool',
            'align' => 'center',
            'active' => 'status',
        ),
        'dcc_addonpayments_subaccount' => array(
            'title' => $this->l('Dynamic Currency Conversion (DCC)'),
            'type' => 'bool',
            'align' => 'center',
            'active' => 'status',
        ),
        'dcc_choice_addonpayments_subaccount' => array(
            'title' => $this->l('1: FEXCO | 2: EUROCONEX'),
            'type' => 'bool',
            'align' => 'center',
        ),
    );

    $helper = new HelperList();
    $helper->shopLinkType = '';
    $helper->simple_header = true;
    $helper->identifier = 'id_addonpayments_subaccount';
    $helper->table = 'addonpayments_subaccount';
    $helper->actions = array('edit', 'delete');
    $helper->show_toolbar = false;
    $helper->module = $this;
    $helper->title = $this->l('Sub Accounts List');
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

    return $helper->generateList($links, $fields_list);
  }

  /**
   * Create the form that will be displayed in the configuration of your module.
   */
  protected function renderFormList()
  {
    $helper = new HelperForm();

    $helper->show_toolbar = false;
    $helper->table = $this->table;
    $helper->module = $this;
    $helper->default_form_language = $this->context->language->id;
    $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

    $helper->identifier = $this->identifier;
    if (Tools::getIsset('updateaddonpayments_subaccount') && !Tools::getValue('updateaddonpayments_subaccount'))
    {
      $helper->submit_action = 'submitUpdateSubaccount';
    }
    else
    {
      $helper->submit_action = 'submitListAddonpaymentsModule';
    }

    $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');

    $helper->tpl_vars = array(
        'fields_value' => $this->getConfigFormListValues(), /* Add values for your inputs */
        'languages' => $this->context->controller->getLanguages(),
        'id_language' => $this->context->language->id,
    );

    return $helper->generateForm(array($this->getConfigFormList()));
  }

  /**
   * Create the structure of your form.
   */
  protected function getConfigFormList()
  {
    $fields_form = array(
        'form' => array(
            'legend' => array(
                'title' => (Tools::getIsset('updateaddonpayments_subaccount') && !Tools::getValue('updateaddonpayments_subaccount')) ?
                $this->l('Update a SubAccount') : $this->l('Add a new SubAccounts'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'desc' => $this->l('Name for Subaccount (default name Internet)'),
                    'name' => 'ADDONPAYMENTS_SUBACCOUNT_NAME',
                    'label' => $this->l('Subaccount'),
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->l('Cards Type'),
                    'name' => 'ADDONPAYMENTS_CARD_TYPE[]',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => '',
                                'name' => $this->l('Visa'),
                                'val' => 'VISA'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('MasterCard'),
                                'val' => 'MC'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('Maestro'),
                                'val' => 'MC'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('Switch'),
                                'val' => 'SWITCH'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('American Express'),
                                'val' => 'AMEX'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('Delta'),
                                'val' => 'DELTA'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('Diners'),
                                'val' => 'DINERS'
                            ),
                            array(
                                'id' => '',
                                'name' => $this->l('Solo'),
                                'val' => 'SOLO'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('3DSecure'),
                    'name' => 'ADDONPAYMENTS_SUBACCOUNT_3DSECURE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Dynamic Currency Conversion (DCC)'),
                    'name' => 'ADDONPAYMENTS_SUBACCOUNT_DCC',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Fexco o Euroconex'),
                    'name' => 'ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => (Tools::getIsset('updateaddonpayments_subaccount') && !Tools::getValue('updateaddonpayments_subaccount')) ?
                $this->l('Update') : $this->l('Save'),
            ),
        ),
    );

    if (Tools::isSubmit('updateaddonpayments_subaccount'))
    {
      $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'updateSubaccount');
      $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_addonpayments_subaccount');
    }

    return $fields_form;
  }

  /**
   * Set values for the inputs.
   */
  protected function getConfigFormListValues()
  {

    if (!Tools::isSubmit('updateaddonpayments_subaccount'))
    {
      $fields_form = array(
          'ADDONPAYMENTS_SUBACCOUNT_NAME' => Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_NAME', ''),
          'ADDONPAYMENTS_CARD_TYPE' => Tools::getValue('ADDONPAYMENTS_CARD_TYPE'),
          'ADDONPAYMENTS_SUBACCOUNT_3DSECURE' => Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_3DSECURE', false),
          'ADDONPAYMENTS_SUBACCOUNT_DCC' => Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_DCC', false),
          'ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE' => Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE', false),
      );
    }
    else
    {
      $fields_saved = $this->getTableAccount(Tools::getValue('id_addonpayments_subaccount'));
      $fields_form = array(
          'ADDONPAYMENTS_SUBACCOUNT_NAME' => $fields_saved['name_addonpayments_subaccount'],
          'ADDONPAYMENTS_CARD_TYPE' => '',
          'ADDONPAYMENTS_SUBACCOUNT_3DSECURE' => $fields_saved['threeds_addonpayments_subaccount'],
          'ADDONPAYMENTS_SUBACCOUNT_DCC' => $fields_saved['dcc_addonpayments_subaccount'],
          'ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE' => $fields_saved['dcc_choice_addonpayments_subaccount'],
          'id_addonpayments_subaccount' => Tools::getValue('id_addonpayments_subaccount'),
          'updateSubaccount' => '',
      );
    }

    return $fields_form;
  }

  /**
   * Save form data.
   */
  protected function postProcessList()
  {
    $form_values = $this->getConfigFormListValues();
    if (Db::getInstance()->insert('addonpayments_subaccount', array(
                'name_addonpayments_subaccount' => pSQL($form_values['ADDONPAYMENTS_SUBACCOUNT_NAME']),
                'threeds_addonpayments_subaccount' => (int) $form_values['ADDONPAYMENTS_SUBACCOUNT_3DSECURE'],
                'dcc_addonpayments_subaccount' => (int) $form_values['ADDONPAYMENTS_SUBACCOUNT_DCC'],
                'dcc_choice_addonpayments_subaccount' => pSQL($form_values['ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE']),
            ))
    )
    {
      $id_subaccount = Db::getInstance()->Insert_ID();
      foreach ($form_values['ADDONPAYMENTS_CARD_TYPE'] as $card)
      {
        Db::getInstance()->insert('addonpayments_rel_card', array(
            'id_addonpayments_subaccount' => (int) $id_subaccount,
            'addonpayments_card_name' => pSQL($card)
        ));
      }
    }
  }

  /**
   * Save Update SubAccount form data.
   */
  protected function postProcessListUpdate()
  {
    if (Db::getInstance()->update('addonpayments_subaccount', array(
                'name_addonpayments_subaccount' => pSQL(Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_NAME')),
                'threeds_addonpayments_subaccount' => (int) Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_3DSECURE'),
                'dcc_addonpayments_subaccount' => (int) Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_DCC'),
                'dcc_choice_addonpayments_subaccount' => pSQL(Tools::getValue('ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE')),
                    ), 'id_addonpayments_subaccount = ' . (int) Tools::getValue('id_addonpayments_subaccount')
            )
    )
    {
      Db::getInstance()->delete('addonpayments_rel_card', 'id_addonpayments_subaccount = ' . (int) Tools::getValue('id_addonpayments_subaccount'));
      foreach (Tools::getValue('ADDONPAYMENTS_CARD_TYPE') as $card)
      {
        Db::getInstance()->insert('addonpayments_rel_card', array(
            'id_addonpayments_subaccount' => (int) Tools::getValue('id_addonpayments_subaccount'),
            'addonpayments_card_name' => pSQL($card)
        ));
      }
    }
  }

  /**
   * Save Update SubAccount form data.
   */
  protected function postProcessListDelete()
  {
    Db::getInstance()->delete('addonpayments_subaccount', 'id_addonpayments_subaccount = ' . (int) Tools::getValue('id_addonpayments_subaccount'));
    Db::getInstance()->delete('addonpayments_rel_card', 'id_addonpayments_subaccount = ' . (int) Tools::getValue('id_addonpayments_subaccount'));
  }

  /**
   * Return all subaccounts set by the merchant
   * 
   * @return array
   */
  public function getTableAccount($id_addonpayments_subaccount = 0)
  {
    if ($this->active)
    {
      $sql = new DbQuery();
      $sql->from('addonpayments_subaccount', 'asb');
      $sql->leftJoin('addonpayments_rel_card', 'arc', 'arc.id_addonpayments_subaccount = asb.id_addonpayments_subaccount');
      if ($id_addonpayments_subaccount > 0)
      {
        $sql->where('asb.id_addonpayments_subaccount = ' . (int) $id_addonpayments_subaccount);
        return Db::getInstance()->getRow($sql);
      }
      return Db::getInstance()->executeS($sql);
    }
    else
    {
      return false;
    }
  }

  /**
   * Return all subaccounts set by the merchant for listing
   * 
   * @return array
   */
  public function getListTableAccount()
  {
    $sql = 'SELECT asb.id_addonpayments_subaccount, '
            . 'name_addonpayments_subaccount, '
            . 'threeds_addonpayments_subaccount, '
            . 'dcc_addonpayments_subaccount, '
            . 'dcc_choice_addonpayments_subaccount, '
            . 'GROUP_CONCAT(addonpayments_card_name) as `cards`  '
            . 'FROM `'._DB_PREFIX_.'addonpayments_subaccount` asb '
            . 'LEFT JOIN `'._DB_PREFIX_.'addonpayments_rel_card` `arc` ON arc.id_addonpayments_subaccount = asb.id_addonpayments_subaccount '
            . 'GROUP BY name_addonpayments_subaccount';
    return Db::getInstance()->executeS($sql);
  }

  /**
   * Return formatted amount without '.'
   * 
   * @param string $total from RealexRedirectPaymentModuleFrontController::initContent()
   * @return string
   */
  public function getAmountFormat($total)
  {
    $tab = explode('.', $total);
    if (count($tab) == 1)
      return $tab[0] . '00';
    else
    {
      if (Tools::strlen(($tab[1])) == 1)
        $total = $tab[0] . $tab[1] . '0';
      else
        $total = $tab[0] . $tab[1];
    }
    return $total;
  }

  /**
   * Return list of all cards type set by the merchant for customer display
   * @return array
   */
  public function getSelectAccount()
  {
    $accounts = $this->getTableAccount();
    $tab = array();
    $temp = array();
    $i = 0;
    foreach ($accounts as $account)
    {
      $tab_card = explode(',', $account['addonpayments_card_name']);
      foreach ($tab_card as $card)
      {
        if (!in_array($card, $temp))
        {
          $tab[$i]['card'] = $card;
          $tab[$i]['account'] = $account['name_addonpayments_subaccount'];
          $temp[] = $card;
          $i++;
        }
      }
    }
    return $tab;
  }

  /**
   * Return translate transaction result message
   * 
   * @param string $result from realexredirect::manageOrder();
   * @return string
   */
  public function getMsg($result = null)
  {
    switch ($result)
    {
      case '00':
        $retour = $this->l('Payment authorised successfully');
        break;
      case $result >= 300 && $result < 400:
        $retour = $this->l('Error with Addon Payments systems');
        break;
      case $result >= 500 && $result < 600:
        $retour = $this->l('Incorrect XML message formation or content');
        break;
      case '666':
        $retour = $this->l('Client deactivated.');
        break;
      case 'fail_liability':
        $retour = $this->l('3D Secure authentication failure');
        break;
      case '101':
      case '102':
      case '103':
      case $result >= 200 && $result < 300:
      case '999':
      default:
        $retour = $this->l('An error occured during payment.');
        break;
    }
    return $retour;
  }

  /**
   * Return translate AVS result message
   * @param string $response from realexredirect::manageOrder();
   * @return string
   */
  public function getAVSresponse($response = null)
  {
    switch ($response)
    {
      case 'M':
        $retour = $this->l('Matched');
        break;
      case 'N':
        $retour = $this->l('Not Matched');
        break;
      case 'I':
        $retour = $this->l('Problem with check');
        break;
      case 'U':
        $retour = $this->l('Unable to check (not certified etc)');
        break;
      case 'P':
        $retour = $this->l('Partial Match');
        break;
      case 'EE':
      default :
        $retour = $this->l('Error Occured');
        break;
    }
    return $retour;
  }

  public function checkCurrency($cart)
  {
    $currency_order = new Currency($cart->id_currency);
    $currencies_module = $this->getCurrency($cart->id_currency);

    if (is_array($currencies_module))
      foreach ($currencies_module as $currency_module)
        if ($currency_order->id == $currency_module['id_currency'])
          return true;
    return false;
  }

  /**
   * Add the CSS & JavaScript files you want to be loaded in the BO.
   */
  public function hookBackOfficeHeader()
  {
    if (Tools::getValue('module_name') == $this->name)
    {
      $this->context->controller->addJS($this->_path . 'views/js/back.js');
      $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }
  }

  /**
   * Add the CSS & JavaScript files you want to be added on the FO.
   */
  public function hookHeader()
  {
    $this->context->controller->addCSS($this->_path . '/views/css/front.css');
  }

  /**
   * This method is used to render the payment button,
   * Take care if the button should be displayed or not.
   */
  public function hookPaymentOptions($params)
  {
    if (!$this->active)
    {
      return;
    }

    if (!$this->checkCurrency($params['cart']))
    {
      return;
    }

    $newOption = new PaymentOption();
    $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay by Credit Card', array(), 'Modules.Addonpayment.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
            ->setAdditionalInformation($this->fetch('module:addonpayments/views/templates/hook/infobox.tpl'));
    $payment_options = [
        $newOption,
    ];

    return $payment_options;
  }

  /**
   * This hook is used to display the order confirmation page.
   */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        $order = $params['order'];
        $cart = $params['cart'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR'))
            $this->smarty->assign('status', 'ok');

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'shop_name' => Context::getContext()->shop->name,
            'total' => Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH), Currency::getCurrencyInstance((int)$cart->id_currency), false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }

}
