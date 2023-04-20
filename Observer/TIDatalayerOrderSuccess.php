<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MappDigital\Cloud\Helper\Config;

class TIDatalayerOrderSuccess implements ObserverInterface
{
    public function __construct(
        protected Session $checkoutSession,
        protected Config $config
    ) {}

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            $orderIds = $observer->getEvent()->getOrderIds() ?? [];

            if (count($orderIds)) {
                $this->checkoutSession->setData('webtrekk_order_success', $orderIds);
            }
        }
    }
}
