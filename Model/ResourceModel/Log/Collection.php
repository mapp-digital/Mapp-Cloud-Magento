<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
declare(strict_types=1);

namespace MappDigital\Cloud\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MappDigital\Cloud\Api\Data\LogInterface;
use MappDigital\Cloud\Model\Log as LogModel;
use MappDigital\Cloud\Model\ResourceModel\Log as LogResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = LogInterface::LOG_ID;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            LogModel::class,
            LogResource::class
        );
    }
}

