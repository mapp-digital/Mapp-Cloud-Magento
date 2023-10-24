<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Model;

use MappDigital\Cloud\Api\EmailRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;


class EmailRepositoryModel implements EmailRepositoryInterface
{
    /**
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $state
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected TransportBuilder $transportBuilder,
        protected StateInterface $state,
        protected LoggerInterface $logger
    )
    {}

    /**
     * {@inheritdoc}
     */
    public function sendEmail(
        array $templateVars,
        string $emailAddress,
        array $from
    )
    {
        $templateId = self::TRANSACTIONAL_EMAIL_TEMPLATE_ID;

        try {
            $this->inlineTranslation->suspend();
            $storeScope = ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area' => Area::AREA_FRONTEND,
                'store' => Store::DEFAULT_STORE_ID
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
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
