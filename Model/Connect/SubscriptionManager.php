<?php

namespace MappDigital\Cloud\Model\Connect;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Customer\Model\Address\Config as CustomerAddressModelConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\StorageInterface;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Sales\Api\Data\OrderInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use Psr\Log\LoggerInterface;
use Zend_Db_Exception;

class SubscriptionManager
{

    const NEWSLETTER_CHANGELOG_TABLE_NAME = 'mapp_connect_newsletter_cl';
    const ORDER_CHANGELOG_TABLE_NAME = 'mapp_connect_order_cl';

    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private TriggerFactory $triggerFactory;
    protected ScopeConfigInterface $coreConfig;
    protected LoggerInterface $logger;
    protected ConnectHelper $connectHelper;
    protected StorageInterface $storage;
    protected CustomerAddressModelConfig $customerAddressModelConfig;
    protected PaymentDataHelper $paymentHelper;
    protected CatalogProductHelper $productHelper;

    public function __construct(
        ResourceConnection $resource,
        TriggerFactory $triggerFactory,
        ScopeConfigInterface $coreConfig,
        LoggerInterface $logger,
        ConnectHelper $connectHelper,
        StorageInterface $storage,
        CustomerAddressModelConfig $customerAddressModelConfig,
        PaymentDataHelper $paymentHelper,
        CatalogProductHelper $productHelper
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->triggerFactory = $triggerFactory;
        $this->coreConfig = $coreConfig;
        $this->logger = $logger;
        $this->connectHelper = $connectHelper;
        $this->storage = $storage;
        $this->customerAddressModelConfig = $customerAddressModelConfig;
        $this->paymentHelper = $paymentHelper;
        $this->productHelper = $productHelper;
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
            $this->logger->error($exception);
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
            $this->logger->error($exception);
        }
    }

    // -----------------------------------------------
    // NEWSLETTER UPDATES
    // -----------------------------------------------

    /**
     * @param string $email
     * @param bool $isSubscribed
     * @return void
     * @throws LocalizedException
     * @throws GuzzleException
     */
    public function sendNewsletterSubscriptionUpdate(string $email, bool $isSubscribed)
    {
        $data = [
            'email' => $email,
            'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
        ];

        if ($isSubscribed && $this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
            $data['doubleOptIn'] = true;
        }

        if (!$isSubscribed) {
            $data['unsubscribe'] = true;
        }

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
        if ($this->connectHelper->getConfigValue('export', 'customer_enable')
            && $order->getData('customer_is_guest')) {

            $data = [
                'dob' => $order->getCustomerDob(),
                'email' => $order->getCustomerEmail(),
                'firstname' => $order->getCustomerFirstname(),
                'gender' => $order->getCustomerGender(),
                'lastname' => $order->getCustomerLastname(),
                'middlename' => $order->getCustomerMiddlename(),
                'note' => $order->getCustomerNote()
            ];

            $data['group'] = $this->connectHelper->getConfigValue('group', 'guests');

            $this->logger->debug('MappConnect: -- SubscriptionManager -- Sending Guest User To Connect', ['data' => $data]);
            $this->connectHelper->getMappConnectClient()->event('user', $data);
        }
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function sendNewOrderTransaction(OrderInterface $order)
    {
        if ($requireOrderStatusForExport = $this->connectHelper->getConfigValue('export', 'transaction_send_on_status')) {
            if (!$order->dataHasChangedFor(OrderInterface::STATUS) || $order->getStatus() != $requireOrderStatusForExport) {
                return;
            }
        }

        $transactionKey = 'mapp_connect_transaction_' . $order->getId();

        if ($this->connectHelper->getConfigValue('export', 'transaction_enable')
            && $this->storage->getData($transactionKey) != true) {
            $this->logger->debug('Mapp Connect: Order plugin called');
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
                if ($this->connectHelper->getConfigValue('group', 'guests')) {
                    $data['group'] = $this->connectHelper->getConfigValue('group', 'guests');
                }
            }

            if ($messageId) {
                $data['messageId'] = (string)$messageId;
            }

            try {
                $this->logger->debug('MappConnect: -- SubscriptionManager -- Sending Order Transaction Request To Connect', ['data' => $data]);
                $this->connectHelper->getMappConnectClient()->event('transaction', $data);
                $this->storage->setData($transactionKey, true);
            } catch (GuzzleException $exception) {
                $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
                $this->logger->error($exception);
            } catch (Exception $exception) {
                $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
                $this->logger->error($exception);
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
            )->addIndex(
                $this->resource->getIdxName(
                    $changelogTableName,
                    ['subscriber_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['subscriber_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );;

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
            )->addIndex(
                $this->resource->getIdxName(
                    $changelogTableName,
                    ['order_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['order_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            );

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
            $this->connection->quoteIdentifier('status')
        );

        $columnChecks[] = sprintf(
            '(NEW.%1$s = "%2$s")',
            $this->connection->quoteIdentifier('status'),
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
            'entity_id',
            'NEW.`entity_id`'
        );

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
}
