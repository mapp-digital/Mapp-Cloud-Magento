<?php

namespace MappDigital\Cloud\Plugin\Sales\Model\ResourceModel;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Interceptor;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;

class OrderPlugin
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
    public function afterSave(Order $subject, Interceptor $interceptor, OrderInterface $order): OrderInterface
    {
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
