<?php
 namespace MappDigital\Cloud\Framework\Mail;

 use Magento\Framework\Exception\MailException;
 use Magento\Framework\Phrase;

class Transport implements \Magento\Framework\Mail\TransportInterface
{

    protected $parameters;
    protected $messageId;
    protected $mappconnect;

    public function __construct($mappconnect, $messageId, $parameters)
    {
        $this->mappconnect = $mappconnect;
        $this->messageId = $messageId;
        $this->parameters = $parameters;
    }

    public function sendMessage()
    {
        if (!$this->messageId) {
            return 0;
        }
        foreach ($this->parameters['to'] as $to) {
            $data = $this->parameters['params'];
            $data['messageId'] = (string)$this->messageId;
            $data['email'] = $to;

            try {
                $this->mappconnect->event('email', $data);
            } catch (\Exception $e) {
                $this->logger->error('MappConnect: cannot sent email event', ['exception' => $e]);
            }
        }
    }

    public function getMessage()
    {
        return $this->messageId;
    }
}
