<?php

namespace MappDigital\Cloud\Observer\Catalog\Product;

use Magento\Catalog\Model\Product as MagentoProductModel;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Logger\CombinedLogger;

class MappConnectPublishProductUpdate implements ObserverInterface
{
    public function __construct(
        private CombinedLogger $mappCombinedLogger,
        private PublisherInterface $publisher,
        private DeploymentConfig $deploymentConfig,
        private ProductRepository $productRepository,
        private Json $jsonSerializer
    ) {}

    public function execute(Observer $observer)
    {
        try {
            /** @var MagentoProductModel $product */
            $product = $observer->getEvent()->getData('product');
            $this->publisher->publish($this->getPublisherName(), $this->jsonSerializer->serialize(['sku' => $product->getSku()]));
        } catch (\Exception $exception) {
            $this->mappCombinedLogger->critical(
                $exception->getMessage(),
                __CLASS__, __FUNCTION__
            );
        }
    }

    /**
     * @return string
     */
    public function getPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType, __CLASS__,__FUNCTION__);
        return 'mappdigital.cloud.entities.export.catalog.product.' . $queueType;
    }

    /**
     * Check if Amqp is used
     *
     * @return bool
     */
    protected function isAmqp(): bool
    {
        try {
            return (bool)$this->deploymentConfig->get('queue/amqp');
        } catch (\Exception $exception) {
            return false;
        }
    }
}
