<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Catalog\Model\Product as MagentoProductModel;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;

class TIDatalayerAddToCart extends TIDatalayerCartAbstract
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
            $item = $observer->getEvent()->getData('quote_item');
            /** @var MagentoProductModel $product */
            $product = $observer->getEvent()->getData('product');

            if ($product->hasData()) {
                $this->mappProductModel->setProduct($product);

                $this->checkoutSession->setData(
                    'webtrekk_addproduct',
                    DataLayerHelper::merge(
                        $this->getSessionData('webtrekk_addproduct'),
                        $this->getProductData($item)
                    )
                );
            }
            try {
                if ($this->connectHelper->getConfigValue('export', 'abandoned_enable') && $this->customerSession->isLoggedIn()) {
                    $this->publisher->publish($this->getAbandonedCartPublisherName(), $this->jsonSerializer->serialize([
                        'email' => $this->customerSession->getCustomer()->getEmail(),
                        'sku' => $item->getProduct()->getSku(),
                        'createdAt' => $this->timezoneInterface->date()->format('Y-m-d H:i:s'),
                        'price' => $item->getPrice()
                    ]));
                    $this->mappCombinedLogger->debug(
                        'Adding Message To Queue for Abandoned Sku: ' . $item->getProduct()->getSku(),
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

    /**
     * @return string
     */
    public function getPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType, __CLASS__, __FUNCTION__);
        return 'mappdigital.cloud.entities.campaigns.abandoned.cart.' . $queueType;
    }
}
