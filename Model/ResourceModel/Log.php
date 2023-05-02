<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
declare(strict_types=1);

namespace MappDigital\Cloud\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use MappDigital\Cloud\Api\Data\LogInterface;

class Log extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('mappdigital_cloud_log', LogInterface::LOG_ID);
    }
}

