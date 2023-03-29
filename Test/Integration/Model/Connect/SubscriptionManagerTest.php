<?php

namespace MappDigital\Cloud\Test\Integration\Model\Connect;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MappDigital\Cloud\Logger\CombinedLogger;
use PHPUnit\Framework\TestCase;

class SubscriptionManagerTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CombinedLogger
     */
    private $combinedLogger;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->combinedLogger = $this->objectManager->get(CombinedLogger::class);
        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
    }

    /**
     * @magentoAppArea global
     */
    public function testOrderInsertTriggerCreatedSuccessfully()
    {
        $sql = $this->getTriggerSelectSql(
            'sales_order',
            Trigger::EVENT_INSERT
        );

        $result = $this->connection->fetchCol($sql);

        $this->assertTrue(count($result) > 0);
        $this->assertContains('trg_sales_order_after_insert', $result);
    }

    /**
     * @magentoAppArea global
     */
    public function testOrderUpdateTriggerCreatedSuccessfully()
    {
        $sql = $this->getTriggerSelectSql(
            'sales_order',
            Trigger::EVENT_UPDATE
        );

        $result = $this->connection->fetchCol($sql);

        $this->assertTrue(count($result) > 0);
        $this->assertContains('trg_sales_order_after_update', $result);
    }

    /**
     * @magentoAppArea global
     */
    public function testOrderDeleteTriggerCreatedSuccessfully()
    {
        $sql = $this->getTriggerSelectSql(
            'sales_order',
            Trigger::EVENT_DELETE
        );

        $result = $this->connection->fetchCol($sql);

        $this->assertTrue(count($result) > 0);
        $this->assertContains('trg_sales_order_after_delete', $result);
    }

    /**
     * @magentoAppArea global
     */
    public function testNewsletterSubscriberInsertTriggerCreatedSuccessfully()
    {
        $sql = $this->getTriggerSelectSql(
            'newsletter_subscriber',
            Trigger::EVENT_INSERT
        );

        $result = $this->connection->fetchCol($sql);

        $this->assertTrue(count($result) > 0);
        $this->assertContains('trg_newsletter_subscriber_after_insert', $result);
    }

    /**
     * @magentoAppArea global
     */
    public function testNewsletterSubscriberUpdateTriggerCreatedSuccessfully()
    {
        $sql = $this->getTriggerSelectSql(
            'newsletter_subscriber',
            Trigger::EVENT_UPDATE
        );

        $result = $this->connection->fetchCol($sql);

        $this->assertTrue(count($result) > 0);
        $this->assertContains('trg_newsletter_subscriber_after_update', $result);
    }

    /**
     * @magentoAppArea global
     */
    public function testNewsletterSubscriberDeleteTriggerCreatedSuccessfully()
    {
        $sql = $this->getTriggerSelectSql(
            'newsletter_subscriber',
            Trigger::EVENT_DELETE
        );

        $result = $this->connection->fetchCol($sql);

        $this->assertTrue(count($result) > 0);
        $this->assertContains('trg_newsletter_subscriber_after_delete', $result);
    }

    /**
     * @param string $tableName
     * @param string $event
     * @return string
     */
    private function getTriggerSelectSql(string $tableName, string $event): string
    {
        return sprintf(
            'SHOW TRIGGERS WHERE %s = %s and %s = %s',
            $this->connection->quoteIdentifier('table'),
            $this->connection->quote($this->connection->getTableName($tableName)),
            $this->connection->quoteIdentifier('event'),
            $this->connection->quote($event),
        );
    }
}
