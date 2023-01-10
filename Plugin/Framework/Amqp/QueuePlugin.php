<?php

namespace MappDigital\Cloud\Plugin\Framework\Amqp;

use Magento\Framework\Amqp\Queue;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use MappDigital\Cloud\Model\QueueMessage\Trigger\ConsumeQueue;

class QueuePlugin
{
    /**
     * Force `$requeue = true` in the case of a failure
     *
     * @param Queue $subject
     * @param EnvelopeInterface $envelope
     * @param bool $requeue
     * @param string|null $rejectionMessage
     * @return array<int, mixed>
     */
    public function beforeReject(
        Queue $subject,
        EnvelopeInterface $envelope,
        bool $requeue = true,
        string $rejectionMessage = null
    ) : array {

        if ($rejectionMessage === ConsumeQueue::RETRY_MESSAGE) {
            return [$envelope, true];
        }

        return [$envelope, $requeue, $rejectionMessage];
    }
}
