<?php

namespace MappDigital\Cloud\Cron\Log;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use MappDigital\Cloud\Api\Data\LogInterface;
use MappDigital\Cloud\Logger\CombinedLogger;

class Clean
{
    private ResourceConnection $resource;
    private AdapterInterface $connection;
    private CombinedLogger $mappCombinedLogger;
    private ScopeConfigInterface $config;

    public function __construct(
        ResourceConnection $resource,
        CombinedLogger $mappCombinedLogger,
        ScopeConfigInterface $config
    )
    {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->mappCombinedLogger = $mappCombinedLogger;
        $this->config = $config;
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $days = (int)$this->config->getValue(LogInterface::CONFIG_XML_PATH_LOG_LIFETIME);
            $this->connection->beginTransaction();

            if ($days) {
                $this->connection->delete($this->resource->getTableName('mappdigital_cloud_log'), [LogInterface::CREATED_AT . ' < NOW() - INTERVAL ? DAY' => $days]);
            }

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            $this->mappCombinedLogger->error(sprintf('Mapp Connect: -- ERROR -- Cleaning Old Logs: %s', $exception->getMessage()), __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }
    }
}
