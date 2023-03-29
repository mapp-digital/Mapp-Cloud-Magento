<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\QueueMessage\Trigger;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Model\OrderRepository;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Psr\Log\LoggerInterface;
use Throwable;

class ConsumeQueue
{
    const RETRY_MESSAGE = 'MAPP RETRY';
    const MAX_ORDER_RETRY_COUNT = 10;
    const MAX_NEWSLETTER_RETRY_COUNT = 10;

    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private Json $jsonSerializer;
    private OrderRepository $orderRepository;
    private CombinedLogger $mappCombinedLogger;
    private SubscriptionManager $subscriptionManager;
    private PublisherInterface $publisher;
    private ScopeConfigInterface $coreConfig;

    public function __construct(
        ResourceConnection $resource,
        Json $jsonSerializer,
        OrderRepository $orderRepository,
        CombinedLogger $mappCombinedLogger,
        SubscriptionManager $subscriptionManager,
        PublisherInterface $publisher,
        ScopeConfigInterface $coreConfig
    )
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->jsonSerializer = $jsonSerializer;
        $this->orderRepository = $orderRepository;
        $this->mappCombinedLogger = $mappCombinedLogger;
        $this->subscriptionManager = $subscriptionManager;
        $this->publisher = $publisher;
        $this->coreConfig = $coreConfig;
    }

    /**
     * @param string $message
     * @throws Exception
     */
    public function processAll(string $message)
    {
        $updateData = $this->jsonSerializer->unserialize($message);

        if (key_exists('order', $updateData)) {
            $this->sendOrderUpdates($updateData);
        }

        if (key_exists('newsletter', $updateData)) {
            $this->sendNewsletterUpdates($updateData);
        }
    }

    /**
     * @param array $ordersIdsToUpdate
     * @return void
     */
    private function sendOrderUpdates(array $ordersIdsToUpdate)
    {
        $idsToRepublish = [];

        foreach ($ordersIdsToUpdate['order'] as $orderId => $order) {
            try {
                $this->subscriptionManager->sendNewOrderTransaction(
                    $this->orderRepository->get($order['order_id'])
                );
            } catch (NoSuchEntityException $exception) {
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__,__FUNCTION__);
            } catch (Exception | Throwable $exception) {
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__,__FUNCTION__);
                $attempt = $order['attempt'] ?? 0;

                if ($attempt < $this->getMaxOrderRetryCount()) {
                    $idsToRepublish['order'][$orderId] = [
                        'order_id' => $orderId,
                        'attempt' => $attempt + 1
                    ];

                    $this->mappCombinedLogger->info('Adding Retry For Order ID: ' . $orderId, __CLASS__,__FUNCTION__);
                }
            }
        }

        if (count($idsToRepublish)) {
            $this->publisher->publish(
                $this->subscriptionManager->getPublisherName(),
                $this->jsonSerializer->serialize($idsToRepublish)
            );
        }
    }

    /**
     * @param array $newsletterSubscriberIdsToUpdate
     * @return void
     */
    private function sendNewsletterUpdates(array $newsletterSubscriberIdsToUpdate)
    {
        $idsToRepublish = [];

        $subscribers = $this->connection->fetchAll($this->connection->select()->from($this->resource->getTableName('newsletter_subscriber'))
            ->where('subscriber_id in (?)', array_values($newsletterSubscriberIdsToUpdate['newsletter']))) ?? [];

        foreach ($subscribers as $subscriber) {
            try {
                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $subscriber['subscriber_email'],
                    $subscriber['subscriber_status'] === Subscriber::STATUS_SUBSCRIBED,
                    $subscriber['store_id'],
                );
            } catch (NoSuchEntityException $exception) {
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__,__FUNCTION__);
            } catch (Exception | Throwable $exception) {
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__,__FUNCTION__);
                $attempt = $newsletterSubscriberIdsToUpdate['newsletter'][$subscriber['subscriber_id']]['attempt'] ?? 0;
                if ($attempt <= $this->getMaxNewsletterRetryCount()) {
                    $idsToRepublish['newsletter'][$subscriber['subscriber_id']] = [
                        'subscriber_id' => $subscriber['subscriber_id'],
                        'attempt' => $attempt + 1
                    ];
                    $this->mappCombinedLogger->info('Adding Retry For Subscriber ID: ' . $subscriber['subscriber_id'], __CLASS__,__FUNCTION__);
                }
            }
        }

        if (count($idsToRepublish)) {
            $this->publisher->publish(
                $this->subscriptionManager->getPublisherName(),
                $this->jsonSerializer->serialize($idsToRepublish)
            );
        }
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

    /**
     * @return int
     */
    private function getMaxOrderRetryCount(): int
    {
        return $this->coreConfig->getValue(ConnectHelper::XML_PATH_ORDER_RETRY_LIMIT) ?? self::MAX_ORDER_RETRY_COUNT;
    }

    /**
     * @return int
     */
    private function getMaxNewsletterRetryCount(): int
    {
        return $this->coreConfig->getValue(ConnectHelper::XML_PATH_NEWSLETTER_RETRY_LIMIT) ?? self::MAX_NEWSLETTER_RETRY_COUNT;
    }
}