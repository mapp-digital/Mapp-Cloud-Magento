<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Helper;


class TrackingScript
{
    public static function generateJS($config) {
        $requireArray = "'jquery'";
        $requireArgument = "$";
        $addToCartHelper = "";
        $tiLoader = "";
        $gtmLoader = "";
        $gtmAddToCartPush = "";
        $gtmCreateProductArray = "";
        $wtSmartLoader = "";

        if($config['tiEnable'] === "1") {
            $tiLoader = "window._tiConfig={tiId:'{$config['tiId']}',tiDomain:'{$config['tiDomain']}',customDomain:'{$config['customDomain']}',customPath:'{$config['customPath']}'};
                (function(c,d,a,f){c.wts=c.wts||[];var g=function(b){var a='';b.customDomain&&b.customPath?a=b.customDomain+'/'+
                b.customPath:b.tiDomain&&b.tiId&&(a=b.tiDomain+'/resp/api/get/'+b.tiId+'?url='+encodeURIComponent(c.location.href)
                +'&v=5');if(b.option)for(var d in b.option)a+='&'+d+'='+encodeURIComponent(b.option[d]);return a};if(-1===
                d.cookie.indexOf('wt_r=1')){var e=d.getElementsByTagName(a)[0];a=d.createElement(a);a.async=!0;a.onload=function(){
                if('undefined'!==typeof c.wt_r&&!isNaN(c.wt_r)){var b= new Date,a=b.getTime()+1E3*parseInt(c.wt_r);b.setTime(a);
                d.cookie='wt_r=1;path=/;expires='+b.toUTCString()}};a.onerror=function(){'undefined'!==typeof c.wt_mcp_hide&&
                'function'===typeof c.wt_mcp_hide.show&&(c.wt_mcp_hide.show(),c.wt_mcp_hide.show=function(){})};a.src='//'+g(f);
                e.parentNode.insertBefore(a,e)}})(window,document,'script',_tiConfig);";

            $addToCartHelper = "function(conf) {
                if(
                    window._ti &&
                    window._ti.hasOwnProperty('addProductEntityId') &&
                    conf.type === 'before' &&
                    conf.mode === 'page' &&
                    conf.requestCounter === 1
                ) {
                    const dataLayerBackup = JSON.stringify(window._ti);
                    handleAddProductKeys(window._ti);
                    calculatePrices();
                    window._ti.shoppingCartStatus = 'add';
                    if(document.cookie.indexOf('mapp_debug') !== -1) {
                        console.log('Mapp Intelligence Add-To-Cart eventname:', window._ti.addToCartEventName);
                        console.log('Mapp Intelligence Add-To-Cart datalayer:', JSON.parse(JSON.stringify(window._ti)));
                    }
                    window.wts.push(['linkId', window._ti.addToCartEventName]);
                    window.wts.push(['send', 'pageupdate', true]);
                    setTimeout(function() {
                        window.wts.push(['linkId', 'false']);
                        restoreDataLayer(JSON.parse(dataLayerBackup));
                        window.wts.push(['send', 'pageupdate', true]);
                    }, 1000);
                    conf.instance.deactivateRequest = true;
                }
            }";
        }

        if($config["gtm"]["enable"] === "1") {
            $requireArray = "'jquery','wtSmart'";
            $requireArgument = "$, wtSmart";
            $wtSmartLoader = "window.wtSmart = window.wtSmart ? window.wtSmart : wtSmart.use(window, window.document);";
            $gtmCreateProductArray = "var gtmProduct = {
                id: window._ti.productId,
                status: window._ti.shoppingCartStatus ==='add' ? 'basket' : window._ti.shoppingCartStatus,
                cost: window._ti.productCost,
                quantity: window._ti.productQuantity,
                soldOut: window._ti.productSoldOut === '1'
            };
            $.each(window._ti, function(key, value) {
                gtmProduct[key] = value;
            });
            window._ti.gtmProductArray=[gtmProduct];";

            $gtmAddToCartPush = "window[config.gtm.datalayer] = window[config.gtm.datalayer] || [];
                        window[config.gtm.datalayer].push(function() {
                          this.reset();
                        });
                        window.wtSmart.action.data.add('add-to-cart');
                            window[config.gtm.datalayer].push({
                                event: 'mapp.load',
                                mapp: {gtmProductArray: JSON.parse(JSON.stringify(window._ti.gtmProductArray))}
                            });";
            if($config["gtm"]["load"] === "1") {
                $gtmLoader = "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                    })(window,document,'script','{$config['gtm']['datalayer']}','{$config['gtm']['id']}');";
            }
        }

        $acquireLoader = $config['acquire'] ? "(function(e){
            var t=document,n=t.createElement('script');
                        n.async=!0,n.defer=!0,n.src=e,
                        t.getElementsByTagName('head')[0].
                        appendChild(n)})('{$config['acquire']}')" : "";

        return "require([{$requireArray}],
            function({$requireArgument}) {
                'use strict';
                {$tiLoader}
                {$wtSmartLoader}
                {$gtmLoader}
                {$acquireLoader}
                const mappEndpoint = location.protocol + '//' + location.host + '/mappintelligence/data/get/';
                const isProductView = window._ti && window._ti.pageAction && window._ti.pageAction === 'catalog_product_view';
                const calculatePrices = function() {
                    const costs = window._ti.productCost.split(';');
                    const quantities = window._ti.productQuantity.split(';');
                    const result = [];
                    if(costs && quantities) {
                        for(let i = 0; i < costs.length; i++) {
                            result.push(parseFloat(costs[i]) * parseFloat(quantities[i]));
                        }
                        window._ti.productCost = result.join(';');
                        window._ti.productPrice = window._ti.productCost;
                    }
                };
                const handleAddProductKeys = function(productAddDataLayer) {
                    $.each(productAddDataLayer, function(key, value) {
                        const keyBase = key.split('addProduct')[1];
                        if(keyBase) {
                            window._ti['product' + keyBase] = value;
                            delete window._ti[key];
                        }
                    });
                    if(window.window._ti.productCategories) {
                        if(window._ti.productCategories[0]) {
                            window._ti.productCategory = window._ti.productCategories[0];
                        }
                        if(window._ti.productCategories[1]) {
                            window._ti.productSubCategory = window._ti.productCategories[1];
                        }
                    }
                }
                const restoreDataLayer = function(backup) {
                    $.each(window._ti, function(key) {
                        window._ti[key] = 'false';
                    });
                    $.extend(window._ti, backup);
                }
                window._mappAddToCartHelper = {$addToCartHelper};
                window.wts = window.wts || [];
                window.wts.push(['_mappAddToCartHelper']);
                $.ajax({
                    url: mappEndpoint + (isProductView ? '?product=1' : ''),
                    type: 'GET',
                    dataType: 'json',
                    complete: function(response) {
            
                        if(window._ti) {
                            if(response.responseJSON && response.responseJSON.dataLayer) {
                                $.extend(window._ti, response.responseJSON.dataLayer);
                            }
                            window._ti.pageName = location.host + location.pathname;
                            if(isProductView) {
                                window._ti.shoppingCartStatus = 'view';
                                window._ti.productQuantity = '1';
                            }
                            if(window._ti.productCost && window._ti.productQuantity) {
                                calculatePrices();
                            }
                            window._ti.addToCartEventName = response.responseJSON.eventName;
            
                        }
                        const config = response.responseJSON.config;
                        if(config.gtm.enable === '1') {
                            {$gtmCreateProductArray}   
                            window[config.gtm.datalayer] = window[config.gtm.datalayer] || [];
                            window[config.gtm.datalayer].push({
                                event: 'mapp.load',
                                mapp: JSON.parse(JSON.stringify(window._ti))
                            });
                        }
                    },
                    error: function (xhr, status, errorThrown) {
                    }
                });
            
                $(document).on('ajax:addToCart', function() {
                    $.ajax({
                        url: mappEndpoint + '?add=1',
                        type: 'GET',
                        dataType: 'json',
                        complete: function(response) {
                            const config = response.responseJSON.config;
                            const productAddDataLayer = response.responseJSON.dataLayer;
                            const addToCartEventName = response.responseJSON.eventName;
                            if(productAddDataLayer && addToCartEventName) {
                                const dataLayerBackup = JSON.stringify(window._ti);
                                handleAddProductKeys(productAddDataLayer);
                                calculatePrices();
                                window._ti.shoppingCartStatus = 'add';
                                window._ti.productStatus = 'add';
                                if(document.cookie.indexOf('mapp_debug') !== -1) {
                                    console.log('Mapp Intelligence Add-To-Cart eventname:', addToCartEventName);
                                    console.log('Mapp Intelligence Add-To-Cart datalayer:', JSON.parse(JSON.stringify(window._ti)));
                                }
                                if(config.tiEnable === '1') {
                                window.wts.push(['linkId', addToCartEventName]);
                                window.wts.push(['send', 'pageupdate']);
                                }
                                {$gtmCreateProductArray}
                                {$gtmAddToCartPush}
                                window._ti = JSON.parse(dataLayerBackup);
                            }
                        },
                        error: function (xhr, status, errorThrown) {
                        }
                    });
                });
            });";
    }
}