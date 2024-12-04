<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Helper;

class TrackingScript
{
    /**
     * @param $config
     * @return string
     */
    public static function generateJS($config, $productId, $storeCode)
    {
        $_psVersion = "1.2.3";

        $requireArray = "'jquery'";
        $requireArgument = "$";
        $addToCartHelper = "''";
        $tiLoader = "";
        $gtmLoader = "";
        $gtmAddToCartPush = "";
        $gtmCreateProductArray = "";
        $gtmEvent = $config["gtm"]["triggerBasket"] === "mapp.load" ? "window.wtSmart.action.data.add('{$config["gtm"]["event"]}');" : "";
        $wtSmartLoader = "";

        if($config['tiEnable'] === "1") {
            $tiLoader = <<<JS
                window._tiConfig={tiId:'{$config['tiId']}',tiDomain:'{$config['tiDomain']}',customDomain:'{$config['customDomain']}',customPath:'{$config['customPath']}'};
                (function(c,d,a,f){c.wts=c.wts||[];var g=function(b){var a='';b.customDomain&&b.customPath?a=b.customDomain+'/'+
                b.customPath:b.tiDomain&&b.tiId&&(a=b.tiDomain+'/resp/api/get/'+b.tiId+'?url='+encodeURIComponent(c.location.href)
                +'&v=5');if(b.option)for(var d in b.option)a+='&'+d+'='+encodeURIComponent(b.option[d]);return a};if(-1===
                d.cookie.indexOf('wt_r=1')){var e=d.getElementsByTagName(a)[0];a=d.createElement(a);a.async=!0;a.onload=function(){
                if('undefined'!==typeof c.wt_r&&!isNaN(c.wt_r)){var b= new Date,a=b.getTime()+1E3*parseInt(c.wt_r);b.setTime(a);
                d.cookie='wt_r=1;path=/;expires='+b.toUTCString()}};a.onerror=function(){'undefined'!==typeof c.wt_mcp_hide&&
                'function'===typeof c.wt_mcp_hide.show&&(c.wt_mcp_hide.show(),c.wt_mcp_hide.show=function(){})};a.src='//'+g(f);
                e.parentNode.insertBefore(a,e)}})(window,document,'script',_tiConfig);window.wts=window.wts||[];window.wts.push(['_ps', 64, '$_psVersion']);
                JS;

            $addToCartHelper = <<<JS
                function(conf) {
                    var pixel = conf.instance.config;
                    if (conf.type === 'before' && conf.mode === 'click' && pixel.productStatus === 'add') {
                        pixel.contentGroup = {};
                        pixel.customParameter = {};
                    }
                    
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
            }
            JS;
        }

        if($config["gtm"]["enable"] === "1") {
            $requireArray = "'jquery','wtSmart'";
            $requireArgument = "$, wtSmart";
            $wtSmartLoader = "window.wtSmart = window.wtSmart ? window.wtSmart : wtSmart.use(window, window.document);
                window.wtSmart._ps && window.wtSmart._ps(64, '$_psVersion');";
            $gtmCreateProductArray = <<<JS
            if(window._ti.hasOwnProperty('shoppingCartStatus')) {
                var status = 'view';
                if(window._ti.shoppingCartStatus ==='add' || window._ti.shoppingCartStatus ==='del') {
                    status = 'basket';
                }
                if(window._ti.shoppingCartStatus ==='conf') {
                    status = 'confirmation';
                }
                var gtmProduct = {
                    id: window._ti.productId,
                    status: status,
                    cost: window._ti.productCost,
                    quantity: window._ti.productQuantity,
                    soldOut: window._ti.soldOut
                };
                $.each(window._ti, function(key, value) {
                    if (key !== 'gtmProductArray') {
                        gtmProduct[key] = value;
                    }
                    if ($.isArray(value)) {
                        $.each(value, function(arrKey, arrValue) {
                            gtmProduct[key + (arrKey+1)] = arrValue;
                        })
                    }
                });
                window._ti.gtmProductArray=[gtmProduct];
            }
            JS;

            $gtmAddToCartPush = "window[config.gtm.datalayer] = window[config.gtm.datalayer] || [];
                        window[config.gtm.datalayer].push(function() {
                          this.reset();
                        });
                        {$gtmEvent}
                            window[config.gtm.datalayer].push({
                                event: '{$config['gtm']['triggerBasket']}',
                                mapp: {gtmProductArray: JSON.parse(JSON.stringify(window._ti.gtmProductArray))}
                            });";
            if ($config["gtm"]["load"] === "1") {
                $gtmLoader = <<<JS
                    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                    })(window,document,'script','{$config['gtm']['datalayer']}','{$config['gtm']['id']}');
                JS;
            }
        }

        $acquireLoader = $config['acquire'] ? <<<JS
                         (function(e){
                         var t=document,n=t.createElement('script');
                         n.async=!0,n.defer=!0,n.src=e,
                         t.getElementsByTagName('head')[0].
                         appendChild(n)})('{$config['acquire']}');
                         JS
        : "";

        return <<<JS
            require([{$requireArray}], function({$requireArgument}) {
                'use strict';
                {$wtSmartLoader}
                {$gtmLoader}
                {$acquireLoader}

                const mappEndpoint = location.protocol + '//' + location.host + '{$storeCode}/mappintelligence/data/get/';
                const isProductView = window._ti && window._ti.pageAction && window._ti.pageAction === 'catalog_product_view';
                const calculatePrices = () => {
                    const costs = window._ti.productCost.split(';');
                    const quantities = window._ti.productQuantity.split(';');
                    const result = [];
                    if (costs && quantities) {
                        for(let i = 0; i < costs.length; i++) {
                            result.push(parseFloat(costs[i]) * parseFloat(quantities[i]));
                        }
                        window._ti.productCost = result.join(';');
                        window._ti.productPrice = window._ti.productCost;
                    }
                };
                const handleAddProductKeys = (productAddDataLayer) => {
                    $.each(productAddDataLayer, function(key, value) {
                        const keyBase = key.split('addProduct')[1];
                        if(keyBase) {
                            window._ti['product' + keyBase] = value;
                            delete window._ti[key];
                        }
                    });
                    if (window.window._ti.productCategories) {
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
                window.acquireAdd = window.acquireAdd || [];
                window.acquireRemove = window.acquireRemove || [];
                window.acquireWishlist = window.acquireWishlist || [];
                window.wts.push(['_mappAddToCartHelper']);
                window.dataLayer = window.dataLayer || [];
                $.ajax({
                    url: mappEndpoint + (isProductView ? '?product={$productId}' : ''),
                    type: 'GET',
                    dataType: 'json',
                    complete: function(response) {
                        if(window._ti) {
                            if(response.responseJSON && response.responseJSON.dataLayer) {
                                $.extend(window._ti, response.responseJSON.dataLayer);
                            }

                            if(response.responseJSON && response.responseJSON.addToWishlistMapp) {
                                window.dataLayer.push({
                                    event: 'addToWishlistMapp',
                                    addToWishlistMapp: JSON.parse(JSON.stringify(response.responseJSON.addToWishlistMapp))
                                });

                                window.acquireWishlist.push({
                                    event: 'add-to-wishlist-mapp'
                                });

                                if(document.cookie.indexOf('mapp_debug') !== -1) {
                                     console.log('Mapp Intelligence Add-To-Wishlist datalayer:', JSON.parse(JSON.stringify(window.dataLayer)));
                                }
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
                        {$tiLoader}
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

                const mappAddToCart = (data) => {
                    const dataLayerBackup = JSON.stringify(window._ti);
                    handleAddProductKeys(data.productAddDataLayer);
                    calculatePrices();
                    window._ti.shoppingCartStatus = 'add';
                    window._ti.productStatus = 'add';
                    if (document.cookie.indexOf('mapp_debug') !== -1) {
                        console.log('Mapp Intelligence Add-To-Cart eventname:', data.addToCartEventName);
                        console.log('Mapp Intelligence Add-To-Cart datalayer:', JSON.parse(JSON.stringify(window._ti)));
                    }
                    if (data.config.tiEnable === '1') {
                        window.dataLayer.push({
                             event: data.addToCartEventName,
                             addToCartMapp: JSON.parse(JSON.stringify(data.productAddToCartMapp))
                        });
                        if(document.cookie.indexOf('mapp_debug') !== -1) {
                            console.log('Mapp Intelligence Add-To-Cart datalayer:', JSON.parse(JSON.stringify(window.dataLayer)));
                        }
                        window.wts.push(['linkId', data.addToCartEventName]);
                        window.wts.push(['send', 'pageupdate', true]);
                        window.acquireAdd.push({
                            event: data.addToCartEventName
                        });
                    }

                    {$gtmCreateProductArray}
                    {$gtmAddToCartPush}
                    setTimeout(function() {
                        restoreDataLayer(JSON.parse(dataLayerBackup));
                        window.wts.push(['linkId', 'false']);
                    }, 500);
                }
                const mappRemoveFromCart = (data) => {
                    const dataLayerBackup = JSON.stringify(window._ti);
                    handleAddProductKeys(data.productAddDataLayer);
                    calculatePrices();
                    if (document.cookie.indexOf('mapp_debug') !== -1) {
                        console.log('Mapp Intelligence Remove-From-Cart eventname:', data.removeFromCartEventName);
                        console.log('Mapp Intelligence Remove-From-Cart datalayer:', JSON.parse(JSON.stringify(window._ti)));
                    }
                    if (data.config.tiEnable === '1') {
                        window._ti.shoppingCartStatus = 'del';
                        window._ti.productStatus = 'del';
                        window.dataLayer.push({
                            event: data.removeFromCartEventName,
                            removeFromCartMapp: JSON.parse(JSON.stringify(data.productRemoveFromCartMapp))
                        });
                        if (document.cookie.indexOf('mapp_debug') !== -1) {
                            console.log('Mapp Intelligence Remove-From-Cart datalayer:', JSON.parse(JSON.stringify(window.dataLayer)));
                        }
                        window.wts.push(['linkId', data.removeFromCartEventName]);
                        window.wts.push(['send', 'pageupdate', true]);
                        window.wts.push(['st', 'del']);
                    }
                    window.acquireRemove.push({
                            event: data.removeFromCartEventName
                    });
                    setTimeout(function() {
                        restoreDataLayer(JSON.parse(dataLayerBackup));
                        window.wts.push(['linkId', 'false']);
                    }, 500);                   
                }
                
                // from catalog add to cart widget
                $(document).on('ajax:addToCart', function() {
                    $.ajax({
                        url: mappEndpoint + '?add=1',
                        type: 'GET',
                        dataType: 'json',
                        complete: function(response) {
                            const config = response.responseJSON.config;
                            const productAddDataLayer = response.responseJSON.dataLayer;
                            const productAddToCartMapp = response.responseJSON.addToCartMapp;
                            const addToCartEventName = response.responseJSON.eventName;
                            if (productAddDataLayer && addToCartEventName) {
                                mappAddToCart({
                                    config: config,
                                    addToCartEventName: addToCartEventName, 
                                    productAddDataLayer: productAddDataLayer, 
                                    productAddToCartMapp: productAddToCartMapp
                                })
                            }
                        },
                        error: function (xhr, status, errorThrown) {
                        }
                    });
                });
                $(document).on('ajax:removeFromCart', () => {
                    $.ajax({
                        url: mappEndpoint + '?remove=1',
                        type: 'GET',
                        dataType: 'json',
                        complete: function(response) {
                            const config = response.responseJSON.config;
                            const productAddDataLayer = response.responseJSON.dataLayer;
                            const productRemoveFromCartMapp = response.responseJSON.removeFromCartMapp;
                            const removeFromCartEventName = response.responseJSON.eventNameRemove;
                            if (productRemoveFromCartMapp && removeFromCartEventName) {
                                mappRemoveFromCart({
                                    config: config,
                                    removeFromCartEventName: removeFromCartEventName, 
                                    productAddDataLayer: productAddDataLayer, 
                                    productRemoveFromCartMapp: productRemoveFromCartMapp
                                })
                            }
                        },
                        error: function (xhr, status, errorThrown) {
                        }
                    })
                });
                
                // reloading cart page after addition or deletion
                if (window.mappCartUpdated) {
                    if (mappCartUpdated.event === mappCartUpdated.removeFromCartEventName) {
                        mappRemoveFromCart(mappCartUpdated)
                    } else if (mappCartUpdated.event === mappCartUpdated.addToCartEventName) {
                        mappAddToCart(mappCartUpdated)
                    }
                }
            })
            JS;
    }
}
