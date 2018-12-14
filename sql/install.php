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

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'addonpayments_payerref` (
            `id_customer` INT(10) NOT NULL,
            `user_reference` VARCHAR(50) NOT NULL UNIQUE,
            `date_add` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id_customer`)
          )
          ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'addonpayments_paymentref` (
            `id_paymentref` INT(10) NOT NULL AUTO_INCREMENT,
            `user_reference` VARCHAR(50) NOT NULL,
            `refpayment` VARCHAR(50) NOT NULL UNIQUE,
            `paymentname_addonpayments` VARCHAR(128) NULL DEFAULT NULL,
            `type_card_addonpayments` VARCHAR(128) NULL DEFAULT NULL,
            `date_add` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id_paymentref`)
          )
          ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'addonpayments_subaccount` (
            `id_addonpayments_subaccount` INT(10) NOT NULL AUTO_INCREMENT,
            `name_addonpayments_subaccount` VARCHAR(50) NULL DEFAULT NULL,
            `threeds_addonpayments_subaccount` INT(1) NULL DEFAULT "0",
            `dcc_addonpayments_subaccount` INT(1) NULL DEFAULT "0",
            `dcc_choice_addonpayments_subaccount` VARCHAR(50) NULL DEFAULT NULL,
            `dcc_active` INT(1) NOT NULL DEFAULT "0",
            `3d_secured` INT(1) NOT NULL DEFAULT "0",
            `id_shop` INT(1) NOT NULL DEFAULT "1",
            PRIMARY KEY (`id_addonpayments_subaccount`,`id_shop`)
          )
          ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'addonpayments_order_processed` (
            `id_order` INT(10) NOT NULL UNIQUE,
            `HPP_ORDER_ID` VARCHAR(50) NOT NULL UNIQUE,
            `HPP_PAS_REF` VARCHAR(50) NOT NULL UNIQUE,
            `HPP_AUTHCODE` VARCHAR(50) NOT NULL DEFAULT "0",
            `CURRENCY` VARCHAR(50) NOT NULL DEFAULT "EUR",
            `rebated` INT(10) NOT NULL DEFAULT "0",
            PRIMARY KEY (`id_order`)
          )
          ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';



foreach ($sql as $query) {
  if (Db::getInstance()->execute($query) == false) {
    return false;
  }
}
