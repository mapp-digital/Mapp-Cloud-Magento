<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Logger;

use JsonException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use MappDigital\Cloud\Model\LogRepository;
use MappDigital\Cloud\Model\LogFactory;
use Psr\Log\LogLevel;
use MappDigital\Cloud\Api\Data\LogInterface;

class CombinedLogger
{
    private array $levelToMethodMap = [
        LogInterface::LOG_LEVEL_CRITICAL => 'critical',
        LogInterface::LOG_LEVEL_ERROR => 'error',
        LogInterface::LOG_LEVEL_WARNING => 'warning',
        LogInterface::LOG_LEVEL_INFO => 'info',
        LogInterface::LOG_LEVEL_DEBUG => 'debug',
    ];

    public function __construct(
        protected Logger $logger,
        protected LogRepository $logRepository,
        protected LogFactory $logFactory,
        protected ScopeConfigInterface $config,
        protected Json $jsonSerializer
    ) {}

    // -----------------------------------------------
    // ENTRY METHODS
    // -----------------------------------------------

    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param mixed $level The log level (a Monolog, PSR-3 or RFC 5424 level)
     * @param string $message The log message
     * @param mixed[] $context The log context
     *
     * @phpstan-param Level|LevelName|LogLevel::* $level
     */
    public function log(int $level, string $message, string $class, string $function, array $context = []): void
    {
        if ($this->canLog($level)) {
            $this->addRecord($level, $message, $class, $function, $context);
        }
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param mixed[] $context The log context
     */
    public function debug(string $message, string $class, string $function, array $context = []): void
    {
        if ($this->canLog(LogInterface::LOG_LEVEL_DEBUG)) {
            $this->addRecord(LogInterface::LOG_LEVEL_DEBUG, $message, $class, $function, $context);
        }
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param mixed[] $context The log context
     */
    public function info(string $message, string $class, string $function, array $context = []): void
    {
        if ($this->canLog(LogInterface::LOG_LEVEL_INFO)) {
            $this->addRecord(LogInterface::LOG_LEVEL_INFO, $message, $class, $function, $context);
        }
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param mixed[] $context The log context
     */
    public function warning(string $message, string $class, string $function, array $context = []): void
    {
        if ($this->canLog(LogInterface::LOG_LEVEL_WARNING)) {
            $this->addRecord(LogInterface::LOG_LEVEL_WARNING, $message, $class, $function, $context);
        }
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param mixed[] $context The log context
     */
    public function error(string $message, string $class, string $function, array $context = []): void
    {
        if ($this->canLog(LogInterface::LOG_LEVEL_ERROR)) {
            $this->addRecord(LogInterface::LOG_LEVEL_ERROR, $message, $class, $function, $context);
        }
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string $message The log message
     * @param mixed[] $context The log context
     */
    public function critical(string $message, string $class, string $function, array $context = []): void
    {
        if ($this->canLog(LogInterface::LOG_LEVEL_CRITICAL)) {
            $this->addRecord(LogInterface::LOG_LEVEL_CRITICAL, $message, $class, $function, $context);
        }
    }

    // -----------------------------------------------
    // Logger Method
    // -----------------------------------------------

    /**
     * @param int $level
     * @param string $message
     * @param string $class
     * @param string $function
     * @param array $context
     * @return void
     */
    private function addRecord(int $level, string $message, string $class, string $function, array $context = [])
    {
        try {
            if ($this->config->getValue(LogInterface::CONFIG_XML_PATH_LOGGING_FILE_ENABLED, ScopeInterface::SCOPE_STORE) && isset($this->levelToMethodMap[$level])) {
                $method = $this->levelToMethodMap[$level];
                $this->logger->$method($class . '::' . $function . ' ====== ' . $message, $context);
            }

            if ($this->config->getValue(LogInterface::CONFIG_XML_PATH_LOGGING_DB_ENABLED, ScopeInterface::SCOPE_STORE)) {
                if (!$this->isJson($message)) {
                    $message = $this->jsonSerializer->serialize($message);
                }

                /** @var LogInterface $databaseLog */
                $databaseLog = $this->logFactory->create();
                $databaseLog
                    ->setSeverity($level)
                    ->setLogData($message)
                    ->setClassFunction($class . '::' . $function);

                $this->logRepository->save($databaseLog);
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getTraceAsString());
        }
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

    /**
     * Confirms if log level is permissible based upon config value.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param int $level Log level to confirm
     */
    public function canLog(int $level): bool
    {
        if ((int)$this->config->getValue(LogInterface::CONFIG_XML_PATH_LOGGING_ENABLED, ScopeInterface::SCOPE_STORE)
            && $level <= (int)$this->config->getValue(LogInterface::CONFIG_XML_PATH_SEVERITY, ScopeInterface::SCOPE_STORE)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a given string is valid JSON.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isJson(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return true;
    }
}
