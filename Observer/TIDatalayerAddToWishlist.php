<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;

class TIDatalayerAddToWishlist extends TIDatalayerCartAbstract
{
    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            if (
                ($product = $observer->getEvent()->getData('product'))
                    && ($wishlist = $observer->getEvent()->getData('wishlist'))
                    && ($item = $observer->getEvent()->getData('item'))
                    && $product->hasData()
                    && $wishlist->hasData()
                    && $item->hasData()
            ) {
                $this->mappProductModel->setProduct($product);

                $wishlistData['product_id'] = $item->getProductId();
                $wishlistData['product_name'] = $product->getName();
                $wishlistData['product_price'] = $product->getPrice();
                $wishlistData['product_sku'] = $product->getSku();
                $wishlistData['qty'] = $item->getQty();
                $wishlistData['created_at'] = $wishlist->getUpdatedAt();

                try {
                    $wishlistData['currency'] = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
                } catch (NoSuchEntityException $exception) {
                }

                $this->checkoutSession->setData(
                    'webtrekk_addtowishlist',
                    DataLayerHelper::merge(
                        $this->getSessionData('webtrekk_addtowishlist'),
                        $wishlistData
                    )
                );
            }
            try {
                if ($this->connectHelper->getConfigValue('export', 'wishlist_enable')) {
                    $this->publisher->publish($this->getWishlistPublisherName(), $this->jsonSerializer->serialize([
                        'email' => $this->customerSession->getCustomer()->getEmail(),
                        'sku' => $item->getProduct()->getSku(),
                        'createdAt' => $item->getAddedAt(),
                        'price' => ($product->getData('price') * $item->getQty())
                    ]));
                    $this->mappCombinedLogger->debug(
                        'Adding Message To Queue for Wishlist Sku: ' . $item->getProduct()->getSku(),
                        __CLASS__,
                        __FUNCTION__
                    );
                }
            } catch (\Exception $exception) {
                $this->mappCombinedLogger->critical(
                    $exception->getMessage(),
                    __CLASS__,
                    __FUNCTION__
                );
            }
        }
    }
}
