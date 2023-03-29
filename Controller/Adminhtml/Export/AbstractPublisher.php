<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MappDigital\Cloud\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use MappDigital\Cloud\Logger\CombinedLogger;

abstract class AbstractPublisher extends Action
{
    const PUBLISH_KEY = '';

    protected PublisherInterface $publisher;
    protected DeploymentConfig $deploymentConfig;
    protected Json $jsonSerializer;
    protected CombinedLogger $mappCombinedLogger;

    public function __construct(
        Context $context,
        PublisherInterface $publisher,
        DeploymentConfig $deploymentConfig,
        Json $jsonSerializer,
        CombinedLogger $mappCombinedLogger
    ) {
        $this->publisher = $publisher;
        $this->deploymentConfig = $deploymentConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->mappCombinedLogger = $mappCombinedLogger;
        parent::__construct($context);
    }

    /**
     * Publish action
     */
    public function execute()
    {
        $this->publisher->publish(
            $this->getPublisherName(),
            $this->jsonSerializer->serialize([
                static::PUBLISH_KEY => 'Queue Export'
            ])
        );

        $this->messageManager->addSuccessMessage(ucfirst(static::PUBLISH_KEY) . ' Export Message Queued Successfully');
        $this->_redirect->redirect($this->_response, $this->_redirect->getRefererUrl());
    }

    /**
     * @return string
     */
    public function getPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType . ' and adding type ' . ucfirst(static::PUBLISH_KEY), __CLASS__,__FUNCTION__);
        return 'mappdigital.cloud.entities.export.' . $queueType;
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

