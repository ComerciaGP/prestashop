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
{block name="content"}
<iframe src="{$iframe_url|escape:'htmlall':'UTF-8'}" width="600" height="568" id="iframeaddonpayments" scrolling="no"></iframe>
<style type="text/css">
	{literal}div#content-wrapper {text-align: center;}{/literal}
</style>
{/block}
<script>
    window.addEventListener("message", function (hpp_dimensions) {
		var jsonvar = JSON.parse(hpp_dimensions.data);
		var dimensions = JSON.parse(hpp_dimensions.data);
		if (typeof dimensions.iframe !== 'undefined') {
			document.getElementById("iframeaddonpayments").style.height = dimensions.iframe.height;
		}
	});
</script>