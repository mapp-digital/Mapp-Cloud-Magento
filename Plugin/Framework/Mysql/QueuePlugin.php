<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Plugin\Framework\Mysql;

use Magento\MysqlMq\Model\Driver\Queue;
use Magento\Framework\MessageQueue\EnvelopeInterface;
use MappDigital\Cloud\Model\QueueMessage\Trigger\ConsumeQueue;

class QueuePlugin
{
    /**
     * Force `$requeue = true` in the case of a failure in Mapp Requests
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
        ?string $rejectionMessage = null
    ) : array {

        if ($rejectionMessage === ConsumeQueue::RETRY_MESSAGE) {
            return [$envelope, true];
        }

        return [$envelope, $requeue, $rejectionMessage];
    }
}
