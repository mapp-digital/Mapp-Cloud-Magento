<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MappDigital\Cloud\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Zend_Db_Exception;

/**
 * Indexer recurring setup
 *
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Recurring implements InstallSchemaInterface
{
    private SubscriptionManager $subscriptionManager;

    public function __construct(
        SubscriptionManager $subscriptionManager
    ) {
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->subscriptionManager->initAll();
    }
}
