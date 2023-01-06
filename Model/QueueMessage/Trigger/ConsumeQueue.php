<?php

namespace MappDigital\Cloud\Model\QueueMessage\Trigger;

use Magento\Framework\Serialize\Serializer\Json;

class ConsumeQueue
{
    private Json $jsonSerializer;

    public function __construct(
        Json $jsonSerializer,
    )
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    public function processAll(string $message)
    {
        $updateData = $this->jsonSerializer->unserialize($message);
        
    }
}
