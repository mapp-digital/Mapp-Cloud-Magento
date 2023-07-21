<?php

namespace MappDigital\Cloud\Observer\Sales\Model\ResourceModel;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Interceptor;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;

class Order implements ObserverInterface
{
    public function __construct(
        protected ConnectHelper $connectHelper,
        protected SubscriptionManager $subscriptionManager
    ) {}

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
}
