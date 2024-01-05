<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class RemoveFromWishlist extends TIDatalayerCartAbstract
{
    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        if ($this->config->isEnabled()) {
            try {
                if ($this->connectHelper->getConfigValue('export', 'wishlist_enable')) {
                    $wishlistItem = $this->wishlistItem->load($observer->getEvent()->getRequest()->getParam('item'));
                    $product = $this->productRepository->getById($wishlistItem->getProductId());
                    $this->publisher->publish($this->getWishlistPublisherName(), $this->jsonSerializer->serialize([
                        'email' => $this->customerSession->getCustomer()->getEmail(),
                        'productSKU' => $product->getSku(),
                        'delete' => true
                    ]));
                    $this->mappCombinedLogger->debug(
                        'Adding Message To Queue for Removing item from Wishlist Sku: ' . $product->getSku(),
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
