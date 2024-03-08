<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Framework\Mail;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\TransportInterface;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\Client as MappConnectClient;
use Psr\Log\LoggerInterface;

class Transport implements TransportInterface
{
    protected MappConnectClient $mappConnectClient;
    private CombinedLogger $mappCombinedLogger;
    protected array $parameters = [];
    protected string $messageId = '';

    public function __construct(
        MappConnectClient $mappConnectClient,
        string $messageId = '',
        array $parameters = []
    ) {
        $this->mappConnectClient = $mappConnectClient;
        $this->messageId = $messageId;
        $this->parameters = $parameters;

        // Use OM to be backwards compatible
        $this->mappCombinedLogger = ObjectManager::getInstance()->create(CombinedLogger::class);
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

                try {
                    $this->mappCombinedLogger->info(
                        \json_encode(['message' => 'Mapp Connect -- INFO -- Email Being Sent Via Mapp', 'data' => $data])
                        , __CLASS__, __FUNCTION__
                    );
                    $this->mappConnectClient->event('email', $data);
                } catch (GuzzleException $exception) {
                    $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect',
                        __CLASS__, __FUNCTION__, ['exception' => $exception]
                    );
                    $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
                } catch (Exception $exception) {
                    $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred',__CLASS__,
                        __FUNCTION__, ['exception' => $exception]
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

    public function getMessage(){}
}
