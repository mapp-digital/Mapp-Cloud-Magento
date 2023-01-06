<?php

namespace MappDigital\Cloud\Cron\Triggers;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Psr\Log\LoggerInterface;

class Publish
{
    const CHUNK_SIZE = 100;

    private string $currentTime;

    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private PublisherInterface $publisher;
    private DeploymentConfig $deploymentConfig;
    private Json $jsonSerializer;
    private LoggerInterface $logger;

    public function __construct(
        ResourceConnection $resource,
        PublisherInterface $publisher,
        DeploymentConfig $deploymentConfig,
        Json $jsonSerializer,
        LoggerInterface $logger
    )
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->publisher = $publisher;
        $this->deploymentConfig = $deploymentConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;

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
                    $data['newsletter'][$newsletter['subscriber_id']] = $newsletter['subscriber_id'];
                }

                if (count($data)) {
                    $this->publisher->publish(
                        $this->getPublisherName(),
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
                    $data['order'][$order['order_id']] = $order['order_id'];
                }

                if (count($data)) {
                    $this->publisher->publish(
                        $this->getPublisherName(),
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

    /**
     * @return string
     */
    private function getPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        return 'mappdigital.cloud.triggers.consume_' . $queueType;
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

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
