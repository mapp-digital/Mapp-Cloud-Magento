<?php

namespace MappDigital\Cloud\Observer\Sales\Model\ResourceModel;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Interceptor;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Magento\Framework\Serialize\Serializer\Json;

class Order implements ObserverInterface
{
    public function __construct(
        protected ConnectHelper $connectHelper,
        protected SubscriptionManager $subscriptionManager,
        protected PublisherInterface $publisher,
        protected CombinedLogger $mappCombinedLogger,
        protected Json $jsonSerializer
    ) {
    }

    /**
     * @param Order $subject
     * @param Interceptor $interceptor
     * @param OrderInterface $order
     * @return OrderInterface
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function execute(Observer $observer): OrderInterface
    {
        $order = $observer->getOrder();

        $this->updateCartAndWishlistCampaigns($order);

        if (!$this->connectHelper->isLegacySyncEnabled()) {
            return $order;
        }

        if ($this->connectHelper->getConfigValue('export', 'transaction_enable')) {
            $this->subscriptionManager->sendNewOrderTransaction($order);
        } elseif ($this->connectHelper->getConfigValue('export', 'customer_enable')) {
            $this->subscriptionManager->sendNewGuestUserToGroupFromOrder($order);
        }

        return $order;
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function updateCartAndWishlistCampaigns(OrderInterface $order) : void
    {
        if ($order->getCustomerId() && ($this->connectHelper->getConfigValue('export', 'abandoned_enable') || $this->connectHelper->getConfigValue('export', 'wishlist_enable'))) {
            foreach ($order->getAllItems() as $item) {
                try {
                    $this->publisher->publish($this->getAbandonedCartPublisherName(), $this->jsonSerializer->serialize([
                        'email' => $order->getCustomerEmail(),
                        'productSKU' => $item->getProduct()->getSku(),
                        'delete' => true
                    ]));
                    $this->mappCombinedLogger->debug(
                        'Adding Message To Queue for Remove Abandoned Skus on order complete: ' . $item->getProduct()->getSku(),
                        __CLASS__,
                        __FUNCTION__
                    );
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

    /**
     * @return string
     */
    public function getAbandonedCartPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType, __CLASS__, __FUNCTION__);
        return 'mappdigital.cloud.entities.campaigns.abandoned.cart.' . $queueType;
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
