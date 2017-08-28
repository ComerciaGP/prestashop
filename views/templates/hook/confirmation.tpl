{*
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
*}

{if (isset($status) == true) && ($status == 'ok')}
	<h3>{l s='Your order on %shop_name% is complete.' sprintf=['%shop_name%' => $shop_name] d='Modules.Addonpayments.Shop'}</h3>
	<p>
		<br />- {l s='Amount' d='Modules.Addonpayments.Shop'} : <span class="price"><strong>{$total}</strong></span>
		<br />- {l s='Reference' d='Modules.Addonpayments.Shop'} : <span class="reference"><strong>{$reference}</strong></span>
		<br /><br />{l s='An email has been sent with this information.' d='Modules.Addonpayments.Shop'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' d='Modules.Addonpayments.Shop'} <a href="{url entity='contact'}">{l s='expert customer support team.' d='Modules.Addonpayments.Shop'}</a>
	</p>
{else}
	<h3>{l s='Your order on %shop_name% has not been accepted.' sprintf=['%shop_name%' => $shop_name] d='Modules.Addonpayments.Shop'}</h3>
	<p>
		<br />- {l s='Reference' d='Modules.Addonpayments.Shop'} <span class="reference"> <strong>{$reference}</strong></span>
		<br /><br />{l s='Please, try to order again.' d='Modules.Addonpayments.Shop'}
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' d='Modules.Addonpayments.Shop'} <a href="{url entity='contact'}">{l s='expert customer support team.' d='Modules.Addonpayments.Shop'}</a>
	</p>
{/if}
<hr />