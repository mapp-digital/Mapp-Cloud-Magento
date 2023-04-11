<?php

namespace MappDigital\Cloud\Test\Integration\Model\Connect;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Helper\Bootstrap;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use PHPUnit\Framework\TestCase;

class SubscriptionManagerTest extends TestCase
{
    private ?ObjectManagerInterface $objectManager;
    private ?CombinedLogger $combinedLogger;
    private ?ResourceConnection $resource;
    private ?AdapterInterface $connection;
    private ?OrderRepository $orderRepository;
    private ?OrderFactory$orderFactory;
    private ?SubscriptionManager $subscriptionManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->combinedLogger = $this->objectManager->get(CombinedLogger::class);
        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);
        $this->orderFactory = $this->objectManager->get(OrderFactory::class);
        $this->subscriptionManager = $this->objectManager->get(SubscriptionManager::class);
        $this->connection = $this->resource->getConnection();
    }

    /**
     * @magentoConfigFixture current_store mapp_connect/group/guests guest_group_id
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_order_complete.php
     * @throws LocalizedException
     */
    public function testGuestOrderApiDataExportWithTransactionsDisabled()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000333');
        $dataForExport = $this->subscriptionManager->getGuestCustomerDataFromOrderObjectForExport($order);

        $this->assertAllGuestUserFieldsAndKeys($dataForExport);
        $this->assertContains('guest_group_id', $dataForExport);
        $this->assertArrayHasKey('group', $dataForExport);
    }

    /**
     * @magentoConfigFixture current_store mapp_connect/export/transaction_enable 1
     * @magentoConfigFixture current_store mapp_connect/export/transaction_send_on_status processing
     * @magentoConfigFixture current_store mapp_connect/group/guests guest_group_id
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_order_complete.php
     * @throws LocalizedException
     */
    public function testGuestOrderApiDataExportWithTransactionsEnabled()
    {
        $dataForExport = $this->getFullOrderExportDataAndConfirmCustomerDataAndBaseOrderData();

        $this->assertContains('guest_group_id', $dataForExport);
        $this->assertArrayHasKey('group', $dataForExport);
    }


    /**
     * @magentoConfigFixture current_store mapp_connect/export/transaction_enable 1
     * @magentoConfigFixture current_store mapp_connect/export/transaction_send_on_status processing
     * @magentoConfigFixture current_store mapp_connect/group/guests guest_group_id
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_order_complete_not_guest.php
     * @throws LocalizedException
     */
    public function testNotGuestOrderApiDataExportWithTransactionsEnabled()
    {
        $dataForExport = $this->getFullOrderExportDataAndConfirmCustomerDataAndBaseOrderData();

        $this->assertArrayNotHasKey('group', $dataForExport);
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

    /**
     * @param array $dataForExport
     * @return void
     */
    private function assertAllGuestUserFieldsAndKeys(array $dataForExport)
    {
        $baseOrderCustomerData = include __DIR__ . '/../../_files/mapp_order_customer_data_key_and_value_base.php';

        foreach ($baseOrderCustomerData as $key => $value) {
            $this->assertArrayHasKey($key, $dataForExport);
            $this->assertNotNull($dataForExport[$key]);
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getFullOrderExportDataAndConfirmCustomerDataAndBaseOrderData(): array
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000333');
        $dataForExport = $this->subscriptionManager->getFullOrderDataFromOrderObjectForExport($order);
        $this->assertAllGuestUserFieldsAndKeys($dataForExport);

        $baseOrderData = include __DIR__ . '/../../_files/mapp_order_data_key_and_value_base.php';

        foreach ($baseOrderData as $key => $value) {
            $this->assertArrayHasKey($key, $dataForExport);
            $this->assertNotNull($dataForExport[$key]);
        }

        return $dataForExport;
    }
}
