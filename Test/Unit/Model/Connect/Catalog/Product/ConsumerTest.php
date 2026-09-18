<?php

declare(strict_types=1);

namespace MappDigital\Cloud\Test\Unit\Model\Connect\Catalog\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\Cache;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Store\Api\StoreRepositoryInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\Catalog\Product\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MappDigital\Cloud\Model\Connect\Catalog\Product\Consumer
 */
class ConsumerTest extends TestCase
{
    private Consumer $consumer;
    private ConnectHelper&MockObject $connectHelper;
    private CombinedLogger&MockObject $logger;
    private CategoryRepository&MockObject $categoryRepository;
    private Json&MockObject $jsonSerializer;
    private ProductRepository&MockObject $productRepository;
    private ScopeConfigInterface&MockObject $coreConfig;
    private DeploymentConfig&MockObject $deploymentConfig;
    private UrlBuilder&MockObject $imageUrlBuilder;
    private Cache&MockObject $imageCache;
    private GetSalableQuantityDataBySku&MockObject $getSalableQuantityDataBySku;
    private StoreRepositoryInterface&MockObject $storeRepository;
    private IsSourceItemManagementAllowedForProductTypeInterface&MockObject $isSourceItemManagementAllowed;

    protected function setUp(): void
    {
        $this->connectHelper = $this->createMock(ConnectHelper::class);
        $this->logger = $this->createMock(CombinedLogger::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->jsonSerializer = $this->createMock(Json::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->coreConfig = $this->createMock(ScopeConfigInterface::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->imageUrlBuilder = $this->createMock(UrlBuilder::class);
        $this->imageCache = $this->createMock(Cache::class);
        $this->getSalableQuantityDataBySku = $this->createMock(GetSalableQuantityDataBySku::class);
        $this->storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $this->isSourceItemManagementAllowed = $this->createMock(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );

        $this->consumer = new Consumer(
            $this->connectHelper,
            $this->logger,
            $this->categoryRepository,
            $this->jsonSerializer,
            $this->productRepository,
            $this->coreConfig,
            $this->deploymentConfig,
            $this->imageUrlBuilder,
            $this->imageCache,
            $this->getSalableQuantityDataBySku,
            $this->storeRepository,
            $this->isSourceItemManagementAllowed,
        );
    }

    /**
     * @dataProvider unsupportedProductTypeProvider
     */
    public function testGetProductTotalQtyReturnsZeroForUnsupportedProductTypes(string $typeId): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn($typeId);

        $this->isSourceItemManagementAllowed
            ->expects($this->once())
            ->method('execute')
            ->with($typeId)
            ->willReturn(false);

        $this->getSalableQuantityDataBySku
            ->expects($this->never())
            ->method('execute');

        $result = $this->invokeGetProductTotalQty($product);

        $this->assertSame(0, $result);
    }

    public static function unsupportedProductTypeProvider(): array
    {
        return [
            'configurable' => [Configurable::TYPE_CODE],
            'bundle' => ['bundle'],
            'grouped' => ['grouped'],
        ];
    }

    public function testGetProductTotalQtyReturnsSalableQtyForSimpleProduct(): void
    {
        $sku = 'SIMPLE-001';

        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn($sku);

        $this->isSourceItemManagementAllowed
            ->expects($this->once())
            ->method('execute')
            ->with('simple')
            ->willReturn(true);

        $this->getSalableQuantityDataBySku
            ->expects($this->once())
            ->method('execute')
            ->with($sku)
            ->willReturn([
                ['stock_id' => 1, 'stock_name' => 'Default', 'qty' => 50.0, 'manage_stock' => true],
                ['stock_id' => 2, 'stock_name' => 'EU', 'qty' => 30.0, 'manage_stock' => true],
            ]);

        $result = $this->invokeGetProductTotalQty($product);

        $this->assertSame(80, $result);
    }

    public function testGetProductTotalQtyExcludesNonManagedStock(): void
    {
        $sku = 'SIMPLE-002';

        $product = $this->createMock(Product::class);
        $product->method('getTypeId')->willReturn('simple');
        $product->method('getSku')->willReturn($sku);

        $this->isSourceItemManagementAllowed
            ->method('execute')
            ->willReturn(true);

        $this->getSalableQuantityDataBySku
            ->method('execute')
            ->with($sku)
            ->willReturn([
                ['stock_id' => 1, 'stock_name' => 'Default', 'qty' => 25.0, 'manage_stock' => true],
                ['stock_id' => 2, 'stock_name' => 'Unmanaged', 'qty' => null, 'manage_stock' => false],
            ]);

        $result = $this->invokeGetProductTotalQty($product);

        $this->assertSame(25, $result);
    }

    /**
     * Invoke private getProductTotalQty method via reflection.
     */
    private function invokeGetProductTotalQty(Product $product): int
    {
        $method = new \ReflectionMethod(Consumer::class, 'getProductTotalQty');

        return $method->invoke($this->consumer, $product);
    }
}
