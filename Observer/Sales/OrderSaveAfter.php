<?php

namespace MappDigital\Cloud\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\EmailRepositoryModel;

class OrderSaveAfter implements ObserverInterface
{
    const XML_PATH_STORE_NAME = 'trans_email/ident_general/name';
    const XML_PATH_STORE_EMAIL = 'trans_email/ident_general/email';

    public function __construct(
        protected ConnectHelper $mappConnectHelper,
        protected EmailRepositoryModel $emailRepositoryModel
    ) {
    }

    /**
     * Send email for cancel order
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getOrigData('status') != 'canceled' && $order->getStatus() == 'canceled') {
            $this->emailRepositoryModel->sendEmail(
                $order,
                trim($order->getCustomerEmail()),
                [
                    'email' => $this->mappConnectHelper->getConfigValueByPath(self::XML_PATH_STORE_EMAIL),
                    'name' => $this->mappConnectHelper->getConfigValueByPath(self::XML_PATH_STORE_NAME)
                ]
            );
        }
    }
}
