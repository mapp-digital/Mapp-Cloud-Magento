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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use MappDigital\Cloud\Enum\Connect\ConfigurationPaths as ConnectConfigurationPaths;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use Magento\Store\Api\StoreRepositoryInterface;

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
        private Cache $imageCache,
        private GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        private StoreRepositoryInterface $storeRepository,
    ) {}

    /**
     * @param string $productDataJson
     * @return void
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function processMessage(string $productDataJson)
    {
        try {
            $productData = $this->jsonSerializer->unserialize($productDataJson);
            $product = $this->productRepository->get($productData['sku'], false, Store::DEFAULT_STORE_ID, true);

            $this->mappCombinedLogger->info('MappConnect: -- Product Sync Consumer -- Sending Product SKU to Mapp: ' . $product->getSku(), __CLASS__,__FUNCTION__);

            $data = $product->getData();
            $data['productName'] = $product->getName();
            $data['productPrice'] = $product->getPrice() ?: $product->getMinimalPrice() ?? $product->getFinalPrice();
            $data['productSKU'] = $product->getSku();
            $data['productURL'] = $product->getProductUrl();
            $data['category'] = $this->getProductCategoryImplodedString($product);
            $data['stockTotal'] = "{$this->getProductTotalQty($product)}";
            $data['store_id'] = Store::DEFAULT_STORE_ID;
            $this->addMediaUrlsIncludingDomainToData($product, $data);
            $this->addLocalizedDataToProduct($product, $data);

            if (is_object($product->getExtensionAttributes())) {
                $data['extension_attributes'] = $this->getAllProductExtensionAttributesAsArray($product);
            }

            $this->mappCombinedLogger->debug(
                'MappConnect: -- Product Sync Consumer -- Sending Product data mapp: ' . json_encode($data, JSON_PRETTY_PRINT),
                __CLASS__, __FUNCTION__,
                ['data' => $data]
            );

            $this->connectHelper->getMappConnectClient()->event('product', $data);
        } catch (\Exception $exception) {
            $this->mappCombinedLogger->critical(
                'MappConnect: -- Product Sync Consumer -- Error when sending to Mapp: ' . json_encode($exception->getTraceAsString(), JSON_PRETTY_PRINT),
                __CLASS__, __FUNCTION__,
                ['exception' => $exception->getTraceAsString()]
            );
        }
    }

    /**
     * @param Product $product
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputException
     * @throws SkuIsNotAssignedToStockException
     */
    private function getProductTotalQty(Product $product): int
    {
        $qty = 0;

        foreach ($this->getSalableQuantityDataBySku->execute($product->getSku()) as $stockInfo) {
            if ($stockInfo['manage_stock']) {
                $qty += $stockInfo['qty'];
            }
        }

        return $qty;
    }

    /**
     * @param Product $product
     * @param array $data
     * @return void
     *
     */
    private function addMediaUrlsIncludingDomainToData(Product $product, array &$data): void
    {
        if ($this->coreConfig->getValue(ConnectConfigurationPaths::XML_PATH_PRODUCT_SYNC_USE_CACHED_URLS->value, ScopeInterface::SCOPE_STORE)) {
            if ($this->coreConfig->getValue(ConnectConfigurationPaths::XML_PATH_PRODUCT_SYNC_GENERATE_CACHED_URLS->value, ScopeInterface::SCOPE_STORE)) {
                $this->imageCache->generate($product);
            }

            $data['image'] = $this->imageUrlBuilder->getUrl($product->getImage() ?? 'no_selection', 'product_base_image');
            $data['thumbnail'] = $this->imageUrlBuilder->getUrl($product->getThumbnail() ?? 'no_selection', 'product_thumbnail_image');
            $data['small_image'] = $this->imageUrlBuilder->getUrl($product->getSmallImage() ?? 'no_selection', 'product_small_image');
            $data['imageURL'] = $this->imageUrlBuilder->getUrl($product->getImage() ?? 'no_selection', 'product_base_image');
            $data['zoomImageURL'] = $this->imageUrlBuilder->getUrl($product->getImage() ?? 'no_selection', 'product_page_image_large');
        } else {
            $data['image'] = $this->getBaseDomainForImagePath() . $product->getImage();
            $data['thumbnail'] = $this->getBaseDomainForImagePath() . $product->getThumbnail() ?? '';
            $data['small_image'] = $this->getBaseDomainForImagePath() . $product->getSmallImage() ?? '';
            $data['imageURL'] = $this->getBaseDomainForImagePath() . $product->getImage() ?? '';
            $data['zoomImageURL'] = $this->getBaseDomainForImagePath() . $product->getSmallImage() ?? '';
        }

        if (isset($data['media_gallery']['images']) && is_array($data['media_gallery']['images'])) {
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
        $productAvailableInCategory = [];

        foreach ($product->getCategoryIds() ?? [] as $categoryId) {
            try {
                $productAvailableInCategory[] = $this->categoryRepository->get($categoryId)->getName();
            } catch (NoSuchEntityException) {}
        }

        return implode(', ', $productAvailableInCategory ?? []) ?? '';
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

    /**
     * Add localized product data for different stores
     *
     * @param ProductInterface $product
     * @param array $data
     * @return void
     */
    private function addLocalizedDataToProduct(ProductInterface $product, array &$data): void
    {
        try {
            $localizedProductNames = [];
            $localizedDescriptions = [];
            $localizedProductPrices = [];
            $localizedMsrps = [];
            $localizedProductURLs = [];

            $storeList = $this->storeRepository->getList();

            foreach ($storeList as $store) {
                // Skip admin store
                if ($store->getId() == 0) {
                    continue;
                }

                try {
                    // Get country code from store configuration
                    $countryCode = $this->coreConfig->getValue(
                        'general/country/default',
                        ScopeInterface::SCOPE_STORE,
                        $store->getId()
                    );

                    // Get currency code from store configuration
                    $currencyCode = $this->coreConfig->getValue(
                        'currency/options/default',
                        ScopeInterface::SCOPE_STORE,
                        $store->getId()
                    );

                    if (!$countryCode || !$currencyCode) {
                        continue;
                    }

                    // Load product for this store view
                    $storeProduct = $this->productRepository->get(
                        $product->getSku(),
                        false,
                        $store->getId(),
                        true
                    );

                    // Add localized product name
                    if ($storeProduct->getName()) {
                        $localizedProductNames[$countryCode] = $storeProduct->getName();
                    }

                    // Add localized description
                    if ($storeProduct->getDescription()) {
                        $localizedDescriptions[$countryCode] = $storeProduct->getDescription();
                    }

                    // Add localized product price
                    $price = $storeProduct->getPrice() ?:
                        $storeProduct->getMinimalPrice() ??
                        $storeProduct->getFinalPrice();
                    if ($price) {
                        $localizedProductPrices[$currencyCode] = $price;
                    }

                    // Add localized MSRP if available
                    if ($storeProduct->getMsrp()) {
                        $localizedMsrps[$currencyCode] = $storeProduct->getMsrp();
                    }

                    // Add localized product URL
                    if ($storeProduct->getProductUrl()) {
                        $localizedProductURLs[$countryCode] = $storeProduct->getProductUrl();
                    }

                } catch (Exception $e) {
                    $this->mappCombinedLogger->error(
                        'MappConnect: -- Product Sync Consumer -- Error getting localized data for store ' . $store->getId() . ': ' . $e->getMessage(),
                        __CLASS__,
                        __FUNCTION__
                    );
                }
            }

            // Add localized data to the product data
            if (!empty($localizedProductNames)) {
                $data['localizedProductNames'] = $this->jsonSerializer->serialize($localizedProductNames);
            }

            if (!empty($localizedDescriptions)) {
                $data['localizedDescriptions'] = $this->jsonSerializer->serialize($localizedDescriptions);
            }

            if (!empty($localizedProductPrices)) {
                $data['localizedProductPrices'] = $this->jsonSerializer->serialize($localizedProductPrices);
            }

            if (!empty($localizedMsrps)) {
                $data['localizedMsrps'] = $this->jsonSerializer->serialize($localizedMsrps);
            }

            if (!empty($localizedProductURLs)) {
                $data['localizedProductURLs'] = $this->jsonSerializer->serialize($localizedProductURLs);
            }

        } catch (Exception $e) {
            $this->mappCombinedLogger->error(
                'MappConnect: -- Product Sync Consumer -- Error adding localized data: ' . $e->getMessage(),
                __CLASS__,
                __FUNCTION__
            );
        }
    }
}
