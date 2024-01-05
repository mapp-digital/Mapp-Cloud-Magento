<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;

class TIDatalayerRemoveFromCart extends TIDatalayerCartAbstract
{
    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            /** @var Item $item */
            $item = $observer->getEvent()->getData('quote_item');

            if ($item->getProduct()->hasData()) {
                $this->mappProductModel->setProduct($item->getProduct());

                $this->checkoutSession->setData(
                    'webtrekk_removeproduct',
                    DataLayerHelper::merge(
                        $this->getSessionData('webtrekk_removeproduct'),
                        $this->getProductData($item)
                    )
                );
            }
            try {
                if ($this->connectHelper->getConfigValue('export', 'abandoned_enable') && $this->customerSession->isLoggedIn()) {
                    $this->publisher->publish($this->getAbandonedCartPublisherName(), $this->jsonSerializer->serialize([
                        'email' => $this->customerSession->getCustomer()->getEmail(),
                        'productSKU' => $item->getProduct()->getSku(),
                        'delete' => true
                    ]));
                    $this->mappCombinedLogger->debug(
                        'Adding Message To Queue for Removing Abandoned Sku: ' . $item->getProduct()->getSku(),
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
