{*
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
*}

{capture name=path}{l s='Order Payment' mod='addonpayments'}{/capture}
<div>
    <h3>{l s='Order summary' mod='addonpayments'}:</h3>

    {assign var='current_step' value='payment'}
    {include file="$tpl_dir./order-steps.tpl"}

    {if $nbProducts <= 0}
        <ul class="alert alert-info">
            <li>{l s='Your shopping cart is empty.' mod='addonpayments'}.</li>
        </ul>
    {else}
        {if isset($error)}
            <div class="alert alert-danger">
                {$error|escape:'htmlall':'UTF-8'}
            </div>
        {/if}
        <p>
            <strong>{l s='You have chosen to pay by Credit or Debit card.' mod='addonpayments'}</strong>
        </p>
        <p>
            {l s='The total amount of your order is' mod='addonpayments'}
            <span id="amount" class="price">{displayPrice price=$total}</span>
            {if $use_taxes == 1}
                {l s='(tax incl.)' mod='addonpayments'}
            {/if}
        </p>
        {if $realvault=="1" && $payer_exists=="1"}
            <div class="bloc_registered_card">
                <h4>{l s='Registered card' mod='addonpayments'}</h4>
                {if !empty($error)} <br/><span class="error">{$error|escape:'htmlall':'UTF-8'}</span><br/><br/>{/if}
                {if !empty($input_registered)}
                    {$input_registered|escape:'htmlall':'UTF-8'}
                {else}
                    {l s='No card registered' mod='addonpayments'}
                {/if}
            </div>
        {/if}
        <div class="bloc_new_card">
            <form action="{$submit_new|escape:'htmlall':'UTF-8'}" method="post">
                <h4>{l s='New card' mod='addonpayments'}</h4>
                {l s='Please select your card type' mod='addonpayments'}<br/>
                {$input_new|escape:'htmlall':'UTF-8'}
            </form>
        </div>
        <div style="padding-top:10px; padding-bottom:10px">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'htmlall':'UTF-8'}" class="button_large">{l s='Other payment methods' mod='addonpayments'}</a>
        </div>
    {/if}
</div>