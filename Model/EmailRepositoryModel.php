<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Model;

use Magento\Framework\App\Area;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use MappDigital\Cloud\Api\EmailRepositoryInterface;
use Psr\Log\LoggerInterface;

class EmailRepositoryModel implements EmailRepositoryInterface
{
    private StateInterface $inlineTranslation;

    public function __construct(
        protected TransportBuilder $transportBuilder,
        protected StateInterface $state,
        protected LoggerInterface $logger
    ) {
        $this->inlineTranslation = $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmail(
        Order $order,
        string $emailAddress,
        array $from
    ): void {
        $templateId = $order->getCustomerIsGuest() ? self::TRANSACTIONAL_EMAIL_ORDER_CANCEL_GUEST_TEMPLATE_ID : self::TRANSACTIONAL_EMAIL_ORDER_CANCEL_TEMPLATE_ID;
        $templateVars = [
            'order' => $order,
            'customer_name' => !$order->getCustomerIsGuest() ? $order->getCustomerName() : $order->getBillingAddress()->getName()
        ];
        $transportObject = new DataObject($templateVars);
        try {
            $this->inlineTranslation->suspend();
            $storeScope = ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area' => Area::AREA_FRONTEND,
                'store' => Store::DEFAULT_STORE_ID
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($transportObject->getData())
                ->setFrom($from)
                ->addTo($emailAddress)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
