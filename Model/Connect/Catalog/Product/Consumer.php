<?php
namespace MappDigital\Cloud\Model\Connect\Catalog\Product;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\Cache;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use MappDigital\Cloud\Enum\Connect\ConfigurationPaths as ConnectConfigurationPaths;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;

class Consumer
{
    private string $baseDomainForImagePaths = '';

    public function __construct(
        private ConnectHelper $connectHelper,
        private CombinedLogger $mappCombinedLogger,
        private CategoryRepository $categoryRepository,
        private Json $jsonSerializer,
        private ProductRepository $productRepository,
        private ScopeConfigInterface $coreConfig,
        private DeploymentConfig $deploymentConfig,
        private UrlBuilder $imageUrlBuilder,
        private Cache $imageCache
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
        $data['productPrice'] = $product->getPrice() ?: $product->getMinimalPrice() ?? $product->getFinalPrice();
        $data['productSKU'] = $product->getSku();
        $data['productURL'] = $product->getProductUrl();
        $data['category'] = $this->getProductCategoryImplodedString($product);
        $this->addMediaUrlsIncludingDomainToData($product, $data);

        if (is_object($product->getExtensionAttributes())) {
            $data['extension_attributes'] = $this->getAllProductExtensionAttributesAsArray($product);
        }

        $this->mappCombinedLogger->info('MappConnect: -- Product Sync Consumer -- Sending Product SKU to Mapp: ' . $product->getSku(), __CLASS__,__FUNCTION__);
        $this->mappCombinedLogger->debug(
            'MappConnect: -- Product Sync Consumer -- Sending Product data mapp',
            __CLASS__, __FUNCTION__,
            ['data' => $data]
        );

        $this->connectHelper->getMappConnectClient()->event('product', $data);
    }

    /**
     * @param Product $product
     * @param array $data
     * @return void
     *
     */
    private function addMediaUrlsIncludingDomainToData(Product $product, array &$data)
    {
        if ($this->coreConfig->getValue(ConnectConfigurationPaths::XML_PATH_PRODUCT_SYNC_USE_CACHED_URLS->value, ScopeInterface::SCOPE_STORE)) {
            if ($this->coreConfig->getValue(ConnectConfigurationPaths::XML_PATH_PRODUCT_SYNC_GENERATE_CACHED_URLS->value, ScopeInterface::SCOPE_STORE)) {
                $this->imageCache->generate($product);
            }

            $data['image'] = $this->imageUrlBuilder->getUrl($product->getImage(), 'product_base_image');
            $data['thumbnail'] = $this->imageUrlBuilder->getUrl($product->getThumbnail(), 'product_thumbnail_image');
            $data['small_image'] = $this->imageUrlBuilder->getUrl($product->getSmallImage(), 'product_small_image');
            $data['imageURL'] = $this->imageUrlBuilder->getUrl($product->getImage(), 'product_base_image');
            $data['zoomImageURL'] = $this->imageUrlBuilder->getUrl($product->getImage(), 'product_page_image_large');
        } else {
            $data['image'] = $this->getBaseDomainForImagePath() . $product->getImage();
            $data['thumbnail'] = $this->getBaseDomainForImagePath() . $product->getThumbnail();
            $data['small_image'] = $this->getBaseDomainForImagePath() . $product->getSmallImage();
            $data['imageURL'] = $this->getBaseDomainForImagePath() . $product->getImage();
            $data['zoomImageURL'] = $this->getBaseDomainForImagePath() . $product->getSmallImage();
        }

        if ((isset($data['media_gallery']) && isset($data['media_gallery']['images'])) && is_array($data['media_gallery']['images'])) {
            foreach ($data['media_gallery']['images'] as $key => $media) {
                if (isset($media['media_type']) && $media['media_type'] === 'image') {
                    if ($this->coreConfig->getValue(ConnectConfigurationPaths::XML_PATH_PRODUCT_SYNC_USE_CACHED_URLS->value, ScopeInterface::SCOPE_STORE)) {
                        $data['media_gallery']['images'][$key]['file'] = $this->imageUrlBuilder->getUrl($data['media_gallery']['images'][$key]['file'], 'product_base_image');
                    } else {
                        $data['media_gallery']['images'][$key]['file'] = $this->getBaseDomainForImagePath() . $data['media_gallery']['images'][$key]['file'];
                    }
                }
            }
        }
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    private function getProductCategoryImplodedString(ProductInterface $product): string
    {
        foreach ($product->getCategoryIds() ?? [] as $categoryId) {
            try {
                $productAvailableInCategory[] = $this->categoryRepository->get($categoryId)->getName();
            } catch (NoSuchEntityException) {}
        }

        return implode(', ', $productAvailableInCategory ?? null) ?? '';
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function getAllProductExtensionAttributesAsArray(ProductInterface $product): array
    {
        foreach ($product->getExtensionAttributes()?->__toArray() as $dataKey => $extensionAttribute) {
            try {
                /** @var $extensionAttribute ProductExtensionInterface */
                if (is_array($extensionAttribute)) {
                    foreach ($extensionAttribute as $key => $value) {
                        $data['extension_attributes'][$dataKey][$key] = $this->getExtensionAttributeValue($value);
                    }
                } else {
                    $data['extension_attributes'][$dataKey] = $this->getExtensionAttributeValue($extensionAttribute);
                }
            } catch (Exception $exception) {
                $this->mappCombinedLogger->error($exception->getMessage(), __CLASS__, __FUNCTION__);
            }
        }

        return $data ?? [];
    }


    /**
     * @return string
     */
    public function getBaseDomainForImagePath(): string
    {
        if (!$this->baseDomainForImagePaths) {
            $mediaUrl = (string)$this->coreConfig->getValue(Store::XML_PATH_SECURE_BASE_MEDIA_URL);
            $domain = (string)$this->coreConfig->getValue(Store::XML_PATH_SECURE_BASE_URL);

            if (!str_contains($mediaUrl, $domain)) {
                $this->baseDomainForImagePaths = rtrim($mediaUrl,'/');
            } else {
                if (!$this->isPubDocumentRoot()) {
                    $domain .= 'pub/';
                }

                $domain .= 'catalog/product';
                $this->baseDomainForImagePaths = $domain;
            }
        }

        return $this->baseDomainForImagePaths;
    }

    /**
     * @param $extensionAttribute
     * @return mixed
     */
    private function getExtensionAttributeValue($extensionAttribute): mixed
    {
        if (is_object($extensionAttribute) && method_exists($extensionAttribute, 'getData')) {
            return $extensionAttribute->getData();
        }

        return $extensionAttribute;
    }

    /**
     * Check if document root is pub directory
     *
     * @return bool
     */
    protected function isPubDocumentRoot(): bool
    {
        try {
            return $this->deploymentConfig->get('directories/document_root_is_pub') == true;
        } catch (Exception) {
            return false;
        }
    }
}
