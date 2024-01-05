<?php
namespace MappDigital\Cloud\Model\QueueMessage\Campaigns;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;

class AbandonedCart
{
    private string $baseDomainForImagePaths = '';

    public function __construct(
        private ConnectHelper $connectHelper,
        private CombinedLogger $mappCombinedLogger,
        private Json $jsonSerializer
    ) {
    }

    /**
     * @param string $dataJson
     * @return void
     * @throws GuzzleException
     */
    public function processMessage(string $jsonData): void
    {
        try {
            $messageData = $this->jsonSerializer->unserialize($jsonData);

            $this->mappCombinedLogger->info('MappConnect: -- Product Abandoned cart Sync Consumer -- Sending Product SKU to Mapp: ' . $messageData['sku'] ?? $messageData['productSKU'], __CLASS__, __FUNCTION__);

            $this->mappCombinedLogger->debug(
                'MappConnect: -- Product Sync Consumer -- Sending Abandoned cart Product data mapp: ' . json_encode($messageData, JSON_PRETTY_PRINT),
                __CLASS__,
                __FUNCTION__,
                ['data' => $messageData]
            );

            $this->connectHelper->getMappConnectClient()->event('abandonedcart', $messageData);
        } catch (\Exception $exception) {
            $this->mappCombinedLogger->critical(
                'MappConnect: -- Abandoned cart Product Sync Consumer -- Error when sending to Mapp: ' . json_encode($exception->getTraceAsString(), JSON_PRETTY_PRINT),
                __CLASS__,
                __FUNCTION__,
                ['exception' => $exception->getTraceAsString()]
            );
        }
    }
}
