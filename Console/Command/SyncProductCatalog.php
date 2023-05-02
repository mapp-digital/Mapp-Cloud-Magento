<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Observer\Catalog\Product\MappConnectPublishProductUpdate;
use MappDigital\Cloud\Model\Export\Entity\Product as ProductExport;

class SyncProductCatalog extends AbstractCommand
{
    const COMMAND_NAME = "mapp:sync:products";

    public function __construct(
        ResourceConnection $resource,
        State $state,
        private ProductExport $productExport,
        private MappConnectPublishProductUpdate $connectPublishProductUpdate,
        private PublisherInterface $publisher,
        private Json $jsonSerializer,
        $name = null
    ){
        parent::__construct($resource, $state, $name);
    }

    /**
     * @return void
     */
    public function doExecute()
    {
        $this->getOutput()->writeln('<info>Starting Publish Product Sync Messages Into Message Queue ...</info>');
        $products = $this->productExport->getEntitiesForExport();

        while ($product = $products->fetchItem()) {
            $this->publisher->publish($this->connectPublishProductUpdate->getPublisherName(), $this->jsonSerializer->serialize(['sku' => $product->getSku()]));
        }

        $this->getOutput()->writeln('<info>All Messages Published</info>');
    }
}
