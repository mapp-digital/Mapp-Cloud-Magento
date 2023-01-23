<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MappDigital\Cloud\Api\Data;

interface LogInterface
{
    const CONFIG_XML_PATH_LOGGING_ENABLED = 'mapp_logging/general/enable_logging';
    const CONFIG_XML_PATH_LOGGING_DB_ENABLED = 'mapp_logging/general/enable_db_logging';
    const CONFIG_XML_PATH_LOGGING_FILE_ENABLED = 'mapp_logging/general/enable_file_logging';
    const CONFIG_XML_PATH_SEVERITY = 'mapp_logging/general/severity';
    const CONFIG_XML_PATH_LOG_LIFETIME = 'mapp_logging/general/log_lifetime';

    const CLASS_FUNCTION = 'class_function';
    const SEVERITY = 'severity';
    const LOG_DATA = 'log_data';
    const LOG_ID = 'log_id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    const LOG_LEVEL_CRITICAL = 1;
    const LOG_LEVEL_ERROR = 2;
    const LOG_LEVEL_WARNING = 3;
    const LOG_LEVEL_INFO = 4;
    const LOG_LEVEL_DEBUG = 5;

    const ALL_LOG_LEVELS = [
        self::LOG_LEVEL_CRITICAL => 'Critical',
        self::LOG_LEVEL_ERROR => 'Error',
        self::LOG_LEVEL_WARNING => 'Warning',
        self::LOG_LEVEL_INFO => 'Info',
        self::LOG_LEVEL_DEBUG => 'Debug'
    ];

    /**
     * Get log_id
     * @return string|null
     */
    public function getLogId();

    /**
     * Set log_id
     * @param string $logId
     * @return \MappDigital\Cloud\Log\Api\Data\LogInterface
     */
    public function setLogId($logId);

    /**
     * Get severity
     * @return string|null
     */
    public function getSeverity();

    /**
     * Set severity
     * @param string $severity
     * @return \MappDigital\Cloud\Log\Api\Data\LogInterface
     */
    public function setSeverity($severity);

    /**
     * Get log_data
     * @return string|null
     */
    public function getLogData();

    /**
     * Set log_data
     * @param string $logData
     * @return \MappDigital\Cloud\Log\Api\Data\LogInterface
     */
    public function setLogData($logData);

    /**
     * Get class_function
     * @return string|null
     */
    public function getClassFunction();

    /**
     * Set class_function
     * @param string $classFunction
     * @return \MappDigital\Cloud\Log\Api\Data\LogInterface
     */
    public function setClassFunction($classFunction);
}

