<?php

namespace MappDigital\Cloud\Observer\Sales;

use Magento\Framework\Event\ObserverInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\EmailRepositoryModel;
use Magento\Framework\Event\Observer;

class OrderSaveAfter implements ObserverInterface
{

    const XML_PATH_STORE_NAME = 'trans_email/ident_general/name';
    const XML_PATH_STORE_EMAIL = 'trans_email/ident_general/email';

    public function __construct(
        protected ConnectHelper $mappConnectHelper,
        protected EmailRepositoryModel $emailRepositoryModel
    )
    {}

    /**
     * Send email for cancel order
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getState() == 'canceled') {
            $templateVars = [
                'order_id' => $order->getIncrementId()
            ];
            $this->emailRepositoryModel->sendEmail(
                $templateVars,
                trim($order->getCustomerEmail()),
                [
                    'email' => $this->mappConnectHelper->getConfigValueByPath(self::XML_PATH_STORE_EMAIL),
                    'name' => $this->mappConnectHelper->getConfigValueByPath(self::XML_PATH_STORE_NAME)
                ]
            );
        }
    }
}
