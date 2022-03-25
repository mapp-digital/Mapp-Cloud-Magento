<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MappDigital\Cloud\Helper\Config;

class TIDatalayerOrderSuccess implements ObserverInterface
{

    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var Config
     */
    protected $_config;

    /**
     * @param Session $checkoutSession
     * @param Config $config
     */
    public function __construct(Session $checkoutSession, Config $config)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->_config->isEnabled()) {
            $orderIds = $observer->getEvent()->getOrderIds();

            if (!empty($orderIds) && is_array($orderIds)) {
                $this->_checkoutSession->setData('webtrekk_order_success', $orderIds);
            }
        }
    }
}
