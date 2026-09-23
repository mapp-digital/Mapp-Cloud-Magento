<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Framework\Mail;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Mail\TransportInterface;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\Client as MappConnectClient;

class Transport implements TransportInterface
{
    protected MappConnectClient $mappConnectClient;
    private ?CombinedLogger $mappCombinedLogger;
    protected array $parameters = [];
    protected string $messageId = '';

    public function __construct(
        MappConnectClient $mappConnectClient,
        string $messageId = '',
        array $parameters = [],
        ?CombinedLogger $mappCombinedLogger = null
    ) {
        $this->mappConnectClient = $mappConnectClient;
        $this->messageId = $messageId;
        $this->parameters = $parameters;
        $this->mappCombinedLogger = $mappCombinedLogger;
    }

    /**
     * @return int|void
     */
    public function sendMessage()
    {
        if (strlen($this->getMessageId()) === 0) {
            return 0;
        }

        if (isset($this->parameters['to']) && is_array($this->parameters['to'])) {
            foreach ($this->parameters['to'] as $to) {
                $data = $this->parameters['params'];
                $data['messageId'] = $this->getMessageId();
                $data['email'] = $to;

                if (array_key_exists('param_order_created_at_datetime', $data)) {
                    try {
                        $dt = new \DateTime($data['param_order_created_at_datetime'], new \DateTimeZone('UTC'));
                    } catch (\Exception $e) {
                        // Fallback: attempt without timezone then set to UTC
                        $dt = new \DateTime($data['param_order_created_at_datetime']);
                        $dt->setTimezone(new \DateTimeZone('UTC'));
                    }
                    // Overwrite created_at with ISO 8601 UTC, and add alternative representation
                    $data['param_order_created_at_datetime'] = $dt->format('c');
                }

                try {
                    $this->mappCombinedLogger->info(
                        \json_encode(['message' => 'Mapp Connect -- INFO -- Email Being Sent Via Mapp', 'data' => $data]),
                        __CLASS__,
                        __FUNCTION__
                    );
                    $this->mappConnectClient->event('email', $data);
                } catch (GuzzleException $exception) {
                    $this->mappCombinedLogger->error(
                        'Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect',
                        __CLASS__,
                        __FUNCTION__,
                        ['exception' => $exception]
                    );
                    $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
                } catch (Exception $exception) {
                    $this->mappCombinedLogger->error(
                        'Mapp Connect -- ERROR -- A General Error Has Occurred',
                        __CLASS__,
                        __FUNCTION__,
                        ['exception' => $exception]
                    );
                    $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getMessage()
    {
    }
}
