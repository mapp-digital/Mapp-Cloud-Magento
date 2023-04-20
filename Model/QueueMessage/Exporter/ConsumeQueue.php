<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\QueueMessage\Exporter;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Model\Export\Entity\Order;
use MappDigital\Cloud\Model\Export\Entity\Customer;
use MappDigital\Cloud\Model\Export\Entity\Product;

class ConsumeQueue
{
    const RETRY_MESSAGE = 'MAPP RETRY';

    public function __construct(
        private Json $jsonSerializer,
        private Order $orderExporter,
        private Customer $customerExporter,
        private Product $productExporter
    ) {}

    /**
     * @param string $message
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function processAll(string $message)
    {
        $exportEntity = $this->jsonSerializer->unserialize($message);

        if (key_exists('order', $exportEntity)) {
            $this->orderExporter->execute();
        }

        if (key_exists('customer', $exportEntity)) {
            $this->customerExporter->execute();
        }

        if (key_exists('product', $exportEntity)) {
            $this->productExporter->execute();
        }
    }
}
