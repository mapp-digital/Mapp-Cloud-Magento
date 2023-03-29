<?php

namespace MappDigital\Cloud\Test\Integration\Logger;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\LogRepository;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class LoggingTest extends TestCase
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
     * @var LogRepository
     */
    private $logRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->combinedLogger = $this->objectManager->get(CombinedLogger::class);
        $this->resource = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $this->resource->getConnection();
        $this->logRepository = $this->objectManager->get(LogRepository::class);
        $this->filterBuilder = $this->objectManager->get(FilterBuilder::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 5
     * @magentoCache config disabled
     */
    public function testDebugDatabaseLogEntrySuccessWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->debug(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertTrue($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 4
     * @magentoCache config disabled
     */
    public function testInfoDatabaseLogEntrySuccessWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->info(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertTrue($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 3
     * @magentoCache config disabled
     */
    public function testWarningDatabaseLogEntrySuccessWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->warning(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertTrue($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 2
     * @magentoCache config disabled
     */
    public function testErrorDatabaseLogEntrySuccessWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->error(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertTrue($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 1
     * @magentoCache config disabled
     */
    public function testCriticalDatabaseLogEntrySuccessWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->critical(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertTrue($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 4
     * @magentoCache config disabled
     */
    public function testDebugDatabaseLogEntryFailureWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->debug(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertFalse($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 3
     * @magentoCache config disabled
     */
    public function testInfoDatabaseLogEntryFailureWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->info(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertFalse($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 2
     * @magentoCache config disabled
     */
    public function testWarningDatabaseLogEntryFailureWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->warning(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertFalse($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/severity 1
     * @magentoCache config disabled
     */
    public function testErrorDatabaseLogEntryFailureWithLoggingLevel()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->error(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertFalse($this->getLogFromRepository($message));
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture default_store mapp_logging/general/enable_logging 1
     * @magentoConfigFixture default_store mapp_logging/general/enable_db_logging 0
     * @magentoConfigFixture default_store mapp_logging/general/severity 1
     * @magentoCache config disabled
     */
    public function testCriticalDatabaseLogEntryFailureWithLoggingDisabledForDatabaseOnly()
    {
        $message = hash('md5', (string)microtime());

        $this->combinedLogger->critical(
            $message,
            __CLASS__,
            __FUNCTION__
        );

        $this->assertFalse($this->getLogFromRepository($message));
    }

    /**
     * Grab a specific message from the DB logged via one of the tests. This is an isolated class so this method
     * can be used if trying to count logs as well as retrieve them
     *
     * @param string $message
     * @return bool
     */
    private function getLogFromRepository(string $message): bool
    {
        try {
            $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);

            $messageFilter = $this->filterBuilder
                ->setField('log_data')
                ->setValue('%' . $message . '%')
                ->setConditionType('like')
                ->create();

            $this->searchCriteriaBuilder->addFilters([$messageFilter]);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $logs = $this->logRepository->getList($searchCriteria);

            return (bool)$logs->getTotalCount() == 1;
        } catch (LocalizedException $exception) {
            return false;
        }
    }

}
