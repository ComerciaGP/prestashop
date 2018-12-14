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

<div class="panel">
    <div class="row caixabankpayment-header">
        <img src="{$module_dir|escape:'html':'UTF-8'}views/img/addon-payments.png" class="col-xs-12 col-md-4 text-center" id="payment-logo" />
    </div>

    <hr />

    <div class="caixabankpayment-content">
        <div class="row">
            <div class="col-md-6">
                <h5>{l s='To refund this order it through Addon Payments, please, fill the import (it must be total or less of the total import) and click the Rebate button.' mod='addonpayments'}</h5>
                <form action="" method="post">
                    <input type="number" name="import_to_rebate" id="import_to_rebate" min="0" max="{$max_total|escape:'htmlall':'UTF-8'}" value="{$max_total|escape:'htmlall':'UTF-8'}" step="0.001" style="height: 30px; display: inline-block; float: left; margin-right: 3px;">
                    <button type="submit" id="rebate_order_addonpayments" name="rebate_order_addonpayments" class="btn btn-primary hidden">
                    </button>
                </form>
                <button type="submit" id="rebate_order_addonpayments2" name="rebate_order_addonpayments2" class="btn btn-primary">
                    {l s='Rebate' mod='addonpayments'}
                </button>
            </div>
        </div>
    </div>
</div>
{literal}
    <script type="text/javascript">
        $(document).ready(function () {
            $("#rebate_order_addonpayments2").on("click", function (e) {
                e.preventDefault();
                var buttons = {};
                var to_refund = $("#import_to_rebate").val() + currency_sign;
                {/literal}
                var title_message = '{l s='Addon Payments: Rebate' mod='addonpayments'}';
                var subtitle_message = '{l s='Are you sure you want to refund' mod='addonpayments'}' + ' ' + to_refund + ' ' + '{l s='from this order?' mod='addonpayments'}';
                var ok_message = '{l s='Yes' mod='addonpayments'}';
                var ko_message = '{l s='No' mod='addonpayments'}';
                {literal}
                buttons[ok_message] = "rebate_confirmation";
                buttons[ko_message] = "rebate_cancelation";
                fancyChooseBox(subtitle_message, title_message, buttons);
            });
        });
        function rebate_confirmation()
        {
            console.log('here');
            $("#rebate_order_addonpayments").trigger('click');
        }
        function rebate_cancelation()
        {
            console.log('here too');
            return false;
        }
    </script>
{/literal}