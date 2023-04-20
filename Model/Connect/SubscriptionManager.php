<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Connect;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Customer\Model\Address\Config as CustomerAddressModelConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManager;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use Zend_Db_Exception;

class SubscriptionManager
{
    const NEWSLETTER_CHANGELOG_TABLE_NAME = 'mapp_connect_newsletter_cl';
    const ORDER_CHANGELOG_TABLE_NAME = 'mapp_connect_order_cl';

    private AdapterInterface $connection;

    public function __construct(
        private ResourceConnection $resource,
        private TriggerFactory $triggerFactory,
        private ScopeConfigInterface $coreConfig,
        private CombinedLogger $mappCombinedLogger,
        private ConnectHelper $connectHelper,
        private StorageInterface $storage,
        private CustomerAddressModelConfig $customerAddressModelConfig,
        private PaymentDataHelper $paymentHelper,
        private CatalogProductHelper $productHelper,
        private DeploymentConfig $deploymentConfig,
        private StoreManager $storeManager
    ) {
        $this->connection = $resource->getConnection();
    }

    // -----------------------------------------------
    // INITIALISATION
    // -----------------------------------------------

    /**
     * @return void
     */
    public function initAll()
    {
        $this->initOrders();
        $this->initNewsletters();
    }

    /**
     * @return void
     */
    public function initOrders()
    {
        try {
            // No DDL statements within a transaction, so these are outside
            $this->createOrderUpdateTrigger();
            $this->createOrderCreateDeleteTrigger();

            $this->connection->beginTransaction();
            $this->createOrderChangelogTable();
            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            $this->mappCombinedLogger->error($exception->getTraceAsString(), __CLASS__, __FUNCTION__, ['Error' => $exception]);
        }
    }

    /**
     * @return void
     */
    public function initNewsletters()
    {
        try {
            // No DDL statements within a transaction, so these are outside
            $this->createNewsletterUpdateTrigger();
            $this->createNewsletterCreateDeleteTrigger();

            $this->connection->beginTransaction();
            $this->createNewsletterChangelogTable();
            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            $this->mappCombinedLogger->error($exception->getTraceAsString(), __CLASS__, __FUNCTION__, ['Error' => $exception]);
        }
    }

    // -----------------------------------------------
    // NEWSLETTER UPDATES
    // -----------------------------------------------

    /**
     * @param string $email
     * @param bool $isSubscribed
     * @param int|null $storeId
     * @return void
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function sendNewsletterSubscriptionUpdate(string $email, bool $isSubscribed, ?int $storeId = null)
    {
        if (!$this->connectHelper->getMappConnectClient()) {
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(__("Cannot Send Subscription Update to Mapp, Email format is invalid"));
        }

        $data = $this->getDataForNewsletterSubscriptionUpdateExport($email, $isSubscribed, $storeId);

        $this->mappCombinedLogger->info(\json_encode(['Type' => 'Newsletter Subscribe', $data], JSON_PRETTY_PRINT), __CLASS__,__FUNCTION__);
        $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function sendNewGuestUserToGroupFromOrder(OrderInterface $order)
    {
        if ($this->connectHelper->getConfigValue('export', 'customer_enable', $order->getStoreId())
            && $order->getData('customer_is_guest')) {
            $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Sending Guest User To Connect', __CLASS__,__FUNCTION__);

            $data = $this->getGuestCustomerDataFromOrderObjectForExport($order);
            $this->mappCombinedLogger->info(\json_encode(['Type' => 'Guest User Group Add', $data], JSON_PRETTY_PRINT), __CLASS__,__FUNCTION__);
            $this->connectHelper->getMappConnectClient()->event('user', $data);
        }
    }

    // -----------------------------------------------
    // ORDER TRANSACTION UPDATES
    // -----------------------------------------------

    /**
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function sendNewOrderTransaction(OrderInterface $order)
    {
        if ($requireOrderStatusForExport = $this->connectHelper->getConfigValue('export', 'transaction_send_on_status', $order->getStoreId())) {
            if ($order->getStatus() != $requireOrderStatusForExport) {
                return;
            }
        }

        $transactionKey = 'mapp_connect_transaction_' . $order->getId();

        if ($this->connectHelper->getConfigValue('export', 'transaction_enable', $order->getStoreId())
            && $this->storage->getData($transactionKey) != true) {
            $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Gathering Order Transaction Data', __CLASS__,__FUNCTION__);
            $data = $this->getFullOrderDataFromOrderObjectForExport($order);
            try {
                $this->mappCombinedLogger->info(\json_encode(['Type' => 'MappConnect: -- SubscriptionManager -- Sending Order Transaction Request To Connect', 'data' => $data], JSON_PRETTY_PRINT), __CLASS__,__FUNCTION__);
                $this->connectHelper->getMappConnectClient()->event('transaction', $data);
                $this->storage->setData($transactionKey, true);
            } catch (GuzzleException $exception) {
                $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To MAPP Connect', __CLASS__, __FUNCTION__, ['Error' => $exception]);
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
            } catch (Exception $exception) {
                $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', __CLASS__, __FUNCTION__, ['Error' => $exception]);
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
            }
        }
    }

    // -----------------------------------------------
    // CHANGELOG TABLES
    // -----------------------------------------------

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    private function createNewsletterChangelogTable()
    {
        $changelogTableName = $this->resource->getTableName(self::NEWSLETTER_CHANGELOG_TABLE_NAME);
        if (!$this->connection->isTableExists($changelogTableName)) {
            $table = $this->connection->newTable(
                $changelogTableName
            )->addColumn(
                'queue_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Version ID'
            )->addColumn(
                'subscriber_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'unique' => true],
                'Entity ID'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )->addIndex(
                $this->resource->getIdxName(
                    $changelogTableName,
                    ['subscriber_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['subscriber_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );

            $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Creating DB Table: ' . self::NEWSLETTER_CHANGELOG_TABLE_NAME, __CLASS__,__FUNCTION__);
            $this->connection->createTable($table);
        }
    }

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    private function createOrderChangelogTable()
    {
        $changelogTableName = $this->resource->getTableName(self::ORDER_CHANGELOG_TABLE_NAME);
        if (!$this->connection->isTableExists($changelogTableName)) {
            $table = $this->connection->newTable(
                $changelogTableName
            )->addColumn(
                'queue_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Version ID'
            )->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Order Entity ID'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )->addIndex(
                $this->resource->getIdxName(
                    $changelogTableName,
                    ['order_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['order_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );

            $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Creating DB Table: ' . self::ORDER_CHANGELOG_TABLE_NAME, __CLASS__,__FUNCTION__);
            $this->connection->createTable($table);
        }
    }

    // -----------------------------------------------
    // NEWSLETTER TRIGGERS
    // -----------------------------------------------

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    private function createNewsletterUpdateTrigger()
    {
        $triggerObject = $this->triggerFactory->create();
        $triggerObject->setName($this->getTriggerName($this->resource->getTableName('newsletter_subscriber'), Trigger::EVENT_UPDATE));
        $triggerObject->setTime(Trigger::TIME_AFTER);
        $triggerObject->setEvent(Trigger::EVENT_UPDATE);
        $triggerObject->setTable($this->resource->getTableName('newsletter_subscriber'));

        $trigger = "INSERT IGNORE INTO %s (%s) VALUES (%s);";

        $columnCheck = sprintf(
            'NOT(NEW.%1$s <=> OLD.%1$s)',
            $this->connection->quoteIdentifier('subscriber_status')
        );

        $trigger = sprintf(
            "IF (%s) THEN %s END IF;",
            $columnCheck,
            $trigger
        );

        $trigger = sprintf(
            $trigger,
            $this->connection->quoteIdentifier($this->resource->getTableName(self::NEWSLETTER_CHANGELOG_TABLE_NAME)),
            'subscriber_id',
            'NEW.`subscriber_id`'
        );

        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Creating DB Trigger: ' . $triggerObject->getName(), __CLASS__,__FUNCTION__);
        $triggerObject->addStatement($trigger);
        $this->connection->dropTrigger($triggerObject->getName());
        $this->connection->createTrigger($triggerObject);
    }

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    private function createNewsletterCreateDeleteTrigger()
    {
        foreach ([Trigger::EVENT_DELETE, Trigger::EVENT_INSERT] as $event) {
            $triggerObject = $this->triggerFactory->create();
            $triggerObject->setName($this->getTriggerName($this->resource->getTableName('newsletter_subscriber'), $event));
            $triggerObject->setTime(Trigger::TIME_AFTER);
            $triggerObject->setEvent($event);
            $triggerObject->setTable($this->resource->getTableName('newsletter_subscriber'));

            $trigger = "INSERT IGNORE INTO %s (%s) VALUES (%s);";

            $trigger = sprintf(
                $trigger,
                $this->connection->quoteIdentifier($this->resource->getTableName(self::NEWSLETTER_CHANGELOG_TABLE_NAME)),
                'subscriber_id',
                ($event == Trigger::EVENT_INSERT) ? 'NEW.`subscriber_id`' : 'OLD.`subscriber_id`'
            );

            $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Creating DB Trigger: ' . $triggerObject->getName(), __CLASS__,__FUNCTION__);
            $triggerObject->addStatement($trigger);
            $this->connection->dropTrigger($triggerObject->getName());
            $this->connection->createTrigger($triggerObject);
        }
    }

    // -----------------------------------------------
    // ORDER TRIGGERS
    // -----------------------------------------------

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    private function createOrderCreateDeleteTrigger()
    {
        foreach ([Trigger::EVENT_DELETE, Trigger::EVENT_INSERT] as $event) {
            $triggerObject = $this->triggerFactory->create();
            $triggerObject->setName($this->getTriggerName($this->resource->getTableName('sales_order'), $event));
            $triggerObject->setTime(Trigger::TIME_AFTER);
            $triggerObject->setEvent($event);
            $triggerObject->setTable($this->resource->getTableName('sales_order'));

            $trigger = "INSERT IGNORE INTO %s (%s) VALUES (%s);";

            $trigger = sprintf(
                $trigger,
                $this->connection->quoteIdentifier($this->resource->getTableName(self::ORDER_CHANGELOG_TABLE_NAME)),
                'order_id',
                ($event === Trigger::EVENT_INSERT) ? 'NEW.`entity_id`' : 'OLD.`entity_id`'
            );

            $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Creating DB Trigger: ' . $triggerObject->getName(), __CLASS__,__FUNCTION__);
            $triggerObject->addStatement($trigger);
            $this->connection->dropTrigger($triggerObject->getName());
            $this->connection->createTrigger($triggerObject);
        }
    }

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    public function createOrderUpdateTrigger(?string $status = null)
    {
        $triggerObject = $this->triggerFactory->create();
        $triggerObject->setName($this->getTriggerName($this->resource->getTableName('sales_order'), Trigger::EVENT_UPDATE));
        $triggerObject->setTime(Trigger::TIME_AFTER);
        $triggerObject->setEvent(Trigger::EVENT_UPDATE);
        $triggerObject->setTable($this->resource->getTableName('sales_order'));

        $trigger = "INSERT IGNORE INTO %s (%s) VALUES (%s);";

        $columnChecks[] = sprintf(
            'NOT(NEW.%1$s <=> OLD.%1$s)',
            $this->connection->quoteIdentifier('state')
        );

        $columnChecks[] = sprintf(
            '(NEW.%1$s = "%2$s")',
            $this->connection->quoteIdentifier('state'),
            $status ?? $this->coreConfig->getValue(ConnectHelper::XML_PATH_ORDER_STATUS_EXPORT)
        );

        $trigger = sprintf(
            "IF (%s) THEN %s END IF;",
            implode(' AND ', $columnChecks),
            $trigger
        );

        $trigger = sprintf(
            $trigger,
            $this->connection->quoteIdentifier($this->resource->getTableName(self::ORDER_CHANGELOG_TABLE_NAME)),
            'order_id',
            'NEW.`entity_id`'
        );

        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Creating DB Trigger: ' . $triggerObject->getName(), __CLASS__,__FUNCTION__);
        $triggerObject->addStatement($trigger);
        $this->connection->dropTrigger($triggerObject->getName());
        $this->connection->createTrigger($triggerObject);
    }

    /**
     * Drop all triggers if legacy method is used
     *
     * @return void
     */
    public function dropAllTriggers()
    {
        foreach ([Trigger::EVENT_DELETE, Trigger::EVENT_INSERT, Trigger::EVENT_UPDATE] as $event) {
            foreach ([$this->resource->getTableName('sales_order'), $this->resource->getTableName('newsletter_subscriber')] as $table) {
                $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Dropping DB Trigger: ' . $this->getTriggerName($table, $event), __CLASS__,__FUNCTION__);
                $this->connection->dropTrigger($this->getTriggerName($table, $event));
            }
        }
    }

    /**
     * @return void
     * @throws Zend_Db_Exception
     */
    public function createAllTriggers()
    {
        $this->createNewsletterCreateDeleteTrigger();
        $this->createNewsletterUpdateTrigger();
        $this->createOrderCreateDeleteTrigger();
        $this->createOrderUpdateTrigger();
    }

    // -----------------------------------------------
    // DATA GETTERS
    // -----------------------------------------------

    /**
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     */
    public function getGuestCustomerDataFromOrderObjectForExport(OrderInterface $order): array
    {
        return [
            'customer_dob' => $order->getCustomerDob(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_firstname' => $order->getCustomerFirstname(),
            'customer_gender' => $order->getCustomerGender(),
            'customer_lastname' => $order->getCustomerLastname(),
            'customer_middlename' => $order->getCustomerMiddlename(),
            'customer_note' => $order->getCustomerNote(),
            'group' => $this->connectHelper->getConfigValue('group', 'guests', $order->getStoreId())
        ];
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function getFullOrderDataFromOrderObjectForExport(OrderInterface $order): array
    {
        $data = $order->getData();
        $data['items'] = [];
        unset($data['status_histories'], $data['extension_attributes'], $data['addresses'], $data['payment']);

        foreach ($order->getAllVisibleItems() as $item) {
            $itemData = $item->getData();
            $itemData['base_image'] = $this->productHelper->getImageUrl($item->getProduct());
            $itemData['url_path'] = $item->getProduct()->getProductUrl();
            $itemData['categories'] = $this->getCategories($item);
            $itemData['manufacturer'] = $item->getProduct()->getAttributeText('manufacturer');
            $itemData['variant'] = $this->getSelectedOptions($item);

            unset($itemData['product_options'], $itemData['extension_attributes'], $itemData['parent_item']);

            $data['items'][] = $itemData;
        }

        if ($billingAddress = $order->getBillingAddress()) {
            $data['billingAddress'] = $billingAddress->getData();
        }

        if ($shippingAddress = $order->getShippingAddress()) {
            $data['shippingAddress'] = $shippingAddress->getData();
        }

        $renderer = $this->customerAddressModelConfig->getFormatByCode('html')->getRenderer();

        $data['billingAddressFormatted'] = $renderer->renderArray($order->getBillingAddress());
        $data['shippingAddressFormatted'] = $renderer->renderArray($order->getShippingAddress());

        $data['payment_info'] = $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $data['store_id']
        );

        $messageId = $this->connectHelper->templateIdToConfig('sales_email_order_template');

        if ($order->getData('customer_is_guest')) {
            $messageId = $this->connectHelper->templateIdToConfig('sales_email_order_guest_template');
            if ($this->connectHelper->getConfigValue('group', 'guests', $order->getStoreId())) {
                $data['group'] = $this->connectHelper->getConfigValue('group', 'guests', $order->getStoreId());
            }
        }

        if ($messageId) {
            $data['messageId'] = (string)$messageId;
        }

        return $data;
    }

    /**
     * @param string $email
     * @param bool $isSubscribed
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getDataForNewsletterSubscriptionUpdateExport(string $email, bool $isSubscribed, ?int $storeId = null): array
    {
        if (is_null($storeId)) {
            $store = $this->storeManager->getStore();
        } else {
            $store = $this->storeManager->getStore($storeId);
        }

        $data = [
            'email' => $email,
            'group' => $this->connectHelper->getConfigValue('group', 'subscribers', $storeId),
            'store_code' => $store->getCode(),
            'store_id' => $store->getId(),
            'store_website_id' => $store->getWebsiteId()
        ];

        if ($isSubscribed && $this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin', $storeId)) {
            $data['doubleOptIn'] = true;
        }

        if (!$isSubscribed) {
            $data['unsubscribe'] = true;
        }

        return $data;
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

    /**
     * @param string $tableName
     * @param string $event
     * @return string
     */
    private function getTriggerName(string $tableName, string $event): string
    {
        return $this->resource->getTriggerName(
            $this->resource->getTableName($tableName),
            Trigger::TIME_AFTER,
            $event
        );
    }

    /**
     * @param $item
     * @return string
     */
    protected function getSelectedOptions($item): string
    {
        $options = $item->getProductOptions();
        $options = array_merge(
            $options['options'] ?? [],
            $options['additional_options'] ?? [],
            $options['attributes_info'] ?? []
        );

        $formattedOptions = [];

        foreach ($options as $option) {
            $formattedOptions[] = $option['label'] . ': ' . $option['value'];
        }

        return implode(', ', $formattedOptions);
    }

    /**
     * @param $item
     * @return string
     */
    protected function getCategories($item): string
    {
        foreach ($item->getProduct()->getCategoryCollection()->addAttributeToSelect('name') as $category) {
            $categories[] = $category->getName();
        }

        return implode(', ', $categories ?? []);
    }

    /**
     * @return string
     */
    public function getPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType, __CLASS__,__FUNCTION__);
        return 'mappdigital.cloud.triggers.consume_' . $queueType;
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
