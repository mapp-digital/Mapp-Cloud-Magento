<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
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
                } catch (NoSuchEntityException $exception) {}

                $this->checkoutSession->setData(
                    'webtrekk_addtowishlist',
                    DataLayerHelper::merge(
                        $this->getSessionData('webtrekk_addtowishlist'),
                        $wishlistData
                    )
                );
            }
        }
    }
}
