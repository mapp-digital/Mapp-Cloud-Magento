<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
declare(strict_types=1);

namespace MappDigital\Cloud\Model;

use Magento\Framework\Model\AbstractModel;
use MappDigital\Cloud\Api\Data\LogInterface;

class Log extends AbstractModel implements LogInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ResourceModel\Log::class);
    }

    /**
     * @inheritDoc
     */
    public function getLogId()
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLogId($logId)
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * @inheritDoc
     */
    public function getSeverity()
    {
        return $this->getData(self::SEVERITY);
    }

    /**
     * @inheritDoc
     */
    public function setSeverity($severity)
    {
        return $this->setData(self::SEVERITY, $severity);
    }

    /**
     * @inheritDoc
     */
    public function getLogData()
    {
        return $this->getData(self::LOG_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setLogData($logData)
    {
        return $this->setData(self::LOG_DATA, $logData);
    }

    /**
     * @inheritDoc
     */
    public function getClassFunction()
    {
        return $this->getData(self::CLASS_FUNCTION);
    }

    /**
     * @inheritDoc
     */
    public function setClassFunction($classFunction)
    {
        return $this->setData(self::CLASS_FUNCTION, $classFunction);
    }
}

