<?php
namespace MappDigital\Cloud\Framework\Mail;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\TransportInterface;
use MappDigital\Cloud\Model\Connect\Client as MappConnectClient;
use Psr\Log\LoggerInterface;

class Transport implements TransportInterface
{
    protected MappConnectClient $mappConnectClient;
    protected LoggerInterface $logger;
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
        $this->logger = ObjectManager::getInstance()->create(LoggerInterface::class);
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
                    $this->mappConnectClient->event('email', $data);
                } catch (GuzzleException $exception) {
                    $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
                    $this->logger->error($exception);
                } catch (Exception $exception) {
                    $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
                    $this->logger->error($exception);
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
