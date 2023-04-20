<?php
namespace MappDigital\Cloud\Model\Connect\Catalog\Product;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;

class Consumer
{
    public function __construct(
        private ConnectHelper $connectHelper,
        private CombinedLogger $mappCombinedLogger,
        private CategoryRepository $categoryRepository,
        private Json $jsonSerializer,
        private ProductFactory $productFactory,
        private DataObjectHelper $dataObjectHelper,
        private ProductRepository $productRepository
    ) {}

    /**
     * @param string $productDataJson
     * @return void
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function processMessage(string $productDataJson)
    {
        $productData = $this->jsonSerializer->unserialize($productDataJson);
        $product = $this->productRepository->get($productData['sku']);

        $data = $product->getData();
        $data['productName'] = $product->getName();
        $data['productPrice'] = $product->getPrice();
        $data['productSKU'] = $product->getSku();
        $data['productURL'] = $product->getUrlKey();
        $data['imageURL'] = $product->getImage();
        $data['zoomImageURL'] = $product->getSmallImage();

        foreach ($product->getCategoryIds() ?? [] as $categoryId) {
            try {
                $productAvailableInCategory[] = $this->categoryRepository->get($categoryId)->getName();
            } catch (NoSuchEntityException $exception) {}
        }

        $data['category'] = implode(', ', $productAvailableInCategory ?? null);

        $this->mappCombinedLogger->critical('MappConnect: -- Product Sync Consumer -- Sending Product SKU to Mapp: ' . $product->getSku(), __CLASS__,__FUNCTION__);
        $this->connectHelper->getMappConnectClient()->event('product', $data);
    }
}
