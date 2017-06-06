{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'switch' && $input.name == 'ADDONPAYMENTS_REDIRECT_SETTLEMENT'}
        <span class="switch prestashop-switch fixed-width-lg">
            {foreach $input.values as $value}
                <input type="radio" name="{$input.name}"{if $value.value == 1} id="{$input.name}_on"{else} id="{$input.name}_off"{/if} value="{$value.value}"{if $fields_value[$input.name] == $value.value} checked="checked"{/if}{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}/>
                {strip}
                    <label {if $value.value == 1} for="{$input.name}_on"{else} for="{$input.name}_off"{/if}>
                        {if $value.value == 1}
                            {l s='Automatic' d='Modules.Addonpayments.Shop'}
                        {else}
                            {l s='Manual' d='Modules.Addonpayments.Shop'}
                        {/if}
                    </label>
                {/strip}
            {/foreach}
            <a class="slide-button btn"></a>
        </span>
    {elseif $input.type == 'switch' && $input.name == 'ADDONPAYMENTS_SUBACCOUNT_DCC_CHOICE'}
        <span class="switch prestashop-switch fixed-width-lg">
            {foreach $input.values as $value}
                <input type="radio" name="{$input.name}"{if $value.value == 1} id="{$input.name}_on"{else} id="{$input.name}_off"{/if} value="{$value.value}"{if $fields_value[$input.name] == $value.value} checked="checked"{/if}{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}/>
                {strip}
                    <label {if $value.value == 1} for="{$input.name}_on"{else} for="{$input.name}_off"{/if}>
                        {if $value.value == 1}
                            {l s='Fexco' d='Modules.Addonpayments.Shop'}
                        {else}
                            {l s='Euroconex' d='Modules.Addonpayments.Shop'}
                        {/if}
                    </label>
                {/strip}
            {/foreach}
            <a class="slide-button btn"></a>
        </span>
    {elseif $input.type == 'switch' && $input.name == 'ADDONPAYMENTS_URLTPV'}
        <span class="switch prestashop-switch fixed-width-lg">
            {foreach $input.values as $value}
                <input type="radio" name="{$input.name}"{if $value.value == 1} id="{$input.name}_on"{else} id="{$input.name}_off"{/if} value="{$value.value}"{if $fields_value[$input.name] == $value.value} checked="checked"{/if}{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}/>
                {strip}
                    <label {if $value.value == 1} for="{$input.name}_on"{else} for="{$input.name}_off"{/if}>
                        {if $value.value == 1}
                            {l s='Real' d='Modules.Addonpayments.Shop'}
                        {else}
                            {l s='Test' d='Modules.Addonpayments.Shop'}
                        {/if}
                    </label>
                {/strip}
            {/foreach}
            <a class="slide-button btn"></a>
        </span>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
