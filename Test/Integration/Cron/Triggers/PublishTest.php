<?php

namespace MappDigital\Cloud\Test\Integration\Cron\Triggers;

use Laminas\Stdlib\Parameters;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber as SubscriberResource;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\TestFramework\TestCase\AbstractController;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use MappDigital\Cloud\Cron\Triggers\Publish;

/**
 * @magentoDbIsolation enabled
 */
class PublishTest extends AbstractController
{
    private ?string $subscriberToDelete = '';

    private ?Session $session = null;
    private ?CustomerRepositoryInterface $customerRepository = null;
    private ?CustomerRegistry $customerRegistry = null;
    private ?Quote $quote = null;
    private ?CheckoutSession $checkoutSession = null;
    private ?QuoteIdMaskFactory $quoteIdMaskFactory = null;
    private ?CartManagementInterface $cartManagement = null;
    private ?GuestCartManagementInterface $guestCartManagement = null;
    private ?OrderRepository $orderRepository = null;
    private ?OrderResource $orderResource = null;
    private ?ResourceConnection $resource = null;
    private ?AdapterInterface $connection = null;
    private ?SubscriberCollectionFactory $subscriberCollectionFactory = null;
    private ?SubscriberResource $subscriberResource = null;
    private ?Publish $cronPubish = null;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->_objectManager->get(Session::class);
        $this->customerRepository = $this->_objectManager->get(CustomerRepositoryInterface::class);
        $this->customerRegistry = $this->_objectManager->get(CustomerRegistry::class);
        $this->quote = $this->_objectManager->get(Quote::class);
        $this->checkoutSession = $this->_objectManager->get(CheckoutSession::class);
        $this->quoteIdMaskFactory = $this->_objectManager->get(QuoteIdMaskFactory::class);
        $this->guestCartManagement = $this->_objectManager->get(GuestCartManagementInterface::class);
        $this->cartManagement = $this->_objectManager->get(CartManagementInterface::class);
        $this->orderRepository = $this->_objectManager->get(OrderRepository::class);
        $this->resource = $this->_objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->subscriberCollectionFactory = $this->_objectManager->get(SubscriberCollectionFactory::class);
        $this->subscriberResource = $this->_objectManager->get(SubscriberResource::class);
        $this->orderResource = $this->_objectManager->get(OrderResource::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->subscriberToDelete) {
            $this->deleteSubscriber($this->subscriberToDelete);
        }

        parent::tearDown();
    }

    /**
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_guest_quote_with_addresses.php
     *
     * @return void
     */
    public function testOrderAddedToChangelogTableWhenOrderCreatedFromGuestQuote()
    {
        $this->quote->load('guest_quote_publish', 'reserved_order_id');
        $order = $this->createAndReturnOrder();

        $this->assertNotNull($order);
        $this->assertIsNumeric($order->getEntityId());

        $result = $this->connection->fetchAll(
                $this->connection->select()->from(
                    $this->connection->getTableName(SubscriptionManager::ORDER_CHANGELOG_TABLE_NAME)
                )
            ) ?? [];

        $this->assertCount(1, $result);
        $this->assertSame($order->getEntityId(), $result[0]['order_id']);
    }

    /**
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_guest_quote_with_addresses.php
     *
     * @return void
     */
    public function testNewsletterSubscriberAddedToChangelogTableOnNewSubscription()
    {
        $this->subscriberToDelete = 'guest@example.com';
        $this->prepareRequest('guest@example.com');
        $this->dispatch('newsletter/subscriber/new');

        $result = $this->connection->fetchAll(
                $this->connection->select()->from(
                    $this->connection->getTableName(SubscriptionManager::NEWSLETTER_CHANGELOG_TABLE_NAME)
                )
            ) ?? [];

        $this->assertCount(1, $result);
    }

    /**
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_guest_quote_with_addresses.php
     *
     * @return void
     */
    public function testCronJobCanUseChangeLogToPublishMessagesForOrders()
    {
        $this->quote->load('guest_quote_publish', 'reserved_order_id');
        $order = $this->createAndReturnOrder();

        $result = $this->connection->fetchAll(
                $this->connection->select()->from(
                    $this->connection->getTableName(SubscriptionManager::ORDER_CHANGELOG_TABLE_NAME)
                )
            ) ?? [];

        $this->assertCount(1, $result);
        $this->assertSame($order->getEntityId(), $result[0]['order_id']);

        sleep(2);
        /**
         * This is being created here as opposed to setUp() due to the `currentTime` being set and used to delete old
         * messages. If this is done in setUp, the current time is _earlier_ than the order creation time as it is
         * set in the construct of the class, thus it doesn't pick up the changelog to publish
         */
        $this->cronPubish = $this->_objectManager->get(Publish::class);
        $this->cronPubish->publishOrders();

        $result = $this->connection->fetchAll(
                $this->connection->select()->from(
                    $this->connection->getTableName(SubscriptionManager::ORDER_CHANGELOG_TABLE_NAME)
                )
            ) ?? [];

        $this->assertCount(0, $result);
    }

    /**
     * Prepare request
     *
     * @param string $email
     * @return void
     */
    private function prepareRequest(string $email): void
    {
        $parameters = $this->_objectManager->create(Parameters::class);
        $parameters->set('HTTP_REFERER', 'http://localhost/testRedirect');
        $this->getRequest()->setServer($parameters);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['email' => $email]);
    }

    /**
     * Delete subscribers by email
     *
     * @param string $email
     * @return void
     */
    private function deleteSubscriber(string $email): void
    {
        $collection = $this->subscriberCollectionFactory->create();
        $item = $collection->addFieldToFilter('subscriber_email', $email)->setPageSize(1)->getFirstItem();
        if ($item->getId()) {
            $this->subscriberResource->delete($item);
        }
    }

    /**
     * @return OrderInterface
     */
    private function createAndReturnOrder()
    {
        $this->checkoutSession->setQuoteId($this->quote->getId());
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->load($this->quote->getId(), 'quote_id');
        $cartId = $quoteIdMask->getMaskedId();
        $orderId = $this->guestCartManagement->placeOrder($cartId);
        return $this->orderRepository->get($orderId);
    }
}
