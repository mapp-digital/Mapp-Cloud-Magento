<?php

namespace MappDigital\Cloud\Cron\Triggers;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Psr\Log\LoggerInterface;

class Publish
{
    const CHUNK_SIZE = 100;

    private string $currentTime;

    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private PublisherInterface $publisher;
    private Json $jsonSerializer;
    private SubscriptionManager $subscriptionManager;
    private CombinedLogger $mappCombinedLogger;

    public function __construct(
        ResourceConnection $resource,
        PublisherInterface $publisher,
        Json $jsonSerializer,
        SubscriptionManager $subscriptionManager,
        CombinedLogger $mappCombinedLogger
    )
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->publisher = $publisher;
        $this->jsonSerializer = $jsonSerializer;
        $this->subscriptionManager = $subscriptionManager;
        $this->mappCombinedLogger = $mappCombinedLogger;

        $currentTime = new \DateTime();
        $this->currentTime = $currentTime->format('Y-m-d H:i:s');
    }

    // -----------------------------------------------
    // PUBLISH METHODS
    // -----------------------------------------------

    /**
     * @return void
     */
    public function publishAll()
    {
        $this->publishNewsletters();
        $this->publishOrders();
    }

    /**
     * @return void
     */
    public function publishNewsletters()
    {
        $newslettersToQueue = $this->getAllNewsletterEntitiesFromChangelog();
        $data = [];

        try {
            $this->connection->beginTransaction();

            foreach (array_chunk($newslettersToQueue, self::CHUNK_SIZE) as $newsletterChunk) {
                foreach ($newsletterChunk as $newsletter) {
                    $data['newsletter'][$newsletter['subscriber_id']] = [
                        'subscriber_id' => $newsletter['subscriber_id'],
                        'attempt' => 1
                    ];
                }

                if (count($data)) {
                    $this->publisher->publish(
                        $this->subscriptionManager->getPublisherName(),
                        $this->jsonSerializer->serialize($data)
                    );
                }

                $data = [];
            }

            $this->connection->delete(
                $this->resource->getTableName(SubscriptionManager::NEWSLETTER_CHANGELOG_TABLE_NAME),
                ['updated_at' . ' < ?' => $this->currentTime]
            );

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }
    }

    /**
     * @return void
     */
    public function publishOrders()
    {
        $ordersToQueue = $this->getAllOrderEntitiesFromChangelog();
        $data = [];

        try {
            $this->connection->beginTransaction();

            foreach (array_chunk($ordersToQueue, self::CHUNK_SIZE) as $orderChunk) {
                foreach ($orderChunk as $order) {
                    $data['order'][$order['order_id']] = [
                        'order_id' => $order['order_id'],
                        'attempt' => 1
                    ];
                }

                if (count($data)) {
                    $this->publisher->publish(
                        $this->subscriptionManager->getPublisherName(),
                        $this->jsonSerializer->serialize($data)
                    );
                }

                $data = [];
            }

            $this->connection->delete(
                $this->resource->getTableName(SubscriptionManager::ORDER_CHANGELOG_TABLE_NAME),
                ['updated_at' . ' < ?' => $this->currentTime]
            );

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }
    }

    // -----------------------------------------------
    // GETTERS
    // -----------------------------------------------

    /**
     * @return array
     */
    private function getAllNewsletterEntitiesFromChangelog(): array
    {
        return $this->connection->fetchAll($this->connection->select()->from($this->resource->getTableName(SubscriptionManager::NEWSLETTER_CHANGELOG_TABLE_NAME))
            ->where('updated_at < (?)', $this->currentTime)) ?? [];
    }

    /**
     * @return array
     */
    private function getAllOrderEntitiesFromChangelog(): array
    {
        return $this->connection->fetchAll($this->connection->select()->from($this->resource->getTableName(SubscriptionManager::ORDER_CHANGELOG_TABLE_NAME))
                ->where('updated_at < (?)', $this->currentTime)) ?? [];
    }
}
