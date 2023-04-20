<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
declare(strict_types=1);

namespace MappDigital\Cloud\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MappDigital\Cloud\Api\Data\LogInterface;
use MappDigital\Cloud\Api\Data\LogInterfaceFactory;
use MappDigital\Cloud\Api\Data\LogSearchResultsInterfaceFactory;
use MappDigital\Cloud\Api\LogRepositoryInterface;
use MappDigital\Cloud\Model\ResourceModel\Log as ResourceLog;
use MappDigital\Cloud\Model\ResourceModel\Log\CollectionFactory as LogCollectionFactory;

class LogRepository implements LogRepositoryInterface
{
    public function __construct(
        protected ResourceLog $resource,
        protected LogInterfaceFactory $logFactory,
        protected LogCollectionFactory $logCollectionFactory,
        protected LogSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    ) {}

    /**
     * @inheritDoc
     */
    public function save(LogInterface $log)
    {
        try {
            $this->resource->save($log);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the log: %1',
                $exception->getMessage()
            ));
        }
        return $log;
    }

    /**
     * @inheritDoc
     */
    public function get($logId)
    {
        $log = $this->logFactory->create();
        $this->resource->load($log, $logId);
        if (!$log->getId()) {
            throw new NoSuchEntityException(__('Log with id "%1" does not exist.', $logId));
        }
        return $log;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->logCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];

        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(LogInterface $log)
    {
        try {
            $logModel = $this->logFactory->create();
            $this->resource->load($logModel, $log->getLogId());
            $this->resource->delete($logModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Log: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($logId)
    {
        return $this->delete($this->get($logId));
    }
}

