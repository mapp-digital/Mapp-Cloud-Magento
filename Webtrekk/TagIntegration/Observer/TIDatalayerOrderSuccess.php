<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webtrekk\TagIntegration\Helper\Data;

class TIDatalayerOrderSuccess implements ObserverInterface
{

    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var Data
     */
    protected $_tiHelper;

    /**
     * @param Session $checkoutSession
     * @param Data $tiHelper
     */
    public function __construct(Session $checkoutSession, Data $tiHelper)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_tiHelper = $tiHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->_tiHelper->isEnabled()) {
            $orderIds = $observer->getEvent()->getOrderIds();

            if (!empty($orderIds) && is_array($orderIds)) {
                $this->_checkoutSession->setData('webtrekk_order_success', $orderIds);
            }
        }
    }
}
