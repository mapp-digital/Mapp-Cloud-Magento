<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Adminhtml\System\Config\Backend\Connect;

use Magento\Config\Model\Config\Backend\Cache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Zend_Db_Exception;

class NewsletterSubscriptionTriggerOnSave extends Value
{
    private SubscriptionManager $subscriptionManager;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SubscriptionManager $subscriptionManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->subscriptionManager = $subscriptionManager;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return NewsletterSubscriptionTriggerOnSave
     */
    public function afterCommitCallback()
    {
        if ($this->isValueChanged()) {
            $this->subscriptionManager->initNewsletters();
        }

        return parent::afterCommitCallback();
    }
}
