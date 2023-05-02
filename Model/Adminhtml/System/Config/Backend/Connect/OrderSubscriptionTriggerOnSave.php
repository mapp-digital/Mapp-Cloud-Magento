<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Adminhtml\System\Config\Backend\Connect;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Zend_Db_Exception;

class OrderSubscriptionTriggerOnSave extends Value
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return OrderSubscriptionTriggerOnSave
     * @throws Zend_Db_Exception
     */
    public function afterCommitCallback()
    {
        if ($this->isValueChanged()) {
            $this->subscriptionManager->createOrderUpdateTrigger();
        }

        return parent::afterCommitCallback();
    }
}
