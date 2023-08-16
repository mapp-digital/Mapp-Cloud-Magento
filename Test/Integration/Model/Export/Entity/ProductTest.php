<?php

namespace MappDigital\Cloud\Test\Integration\Model\Export\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MappDigital\Cloud\Model\Export\Entity\Product;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_product_simple.php
 */
class ProductTest extends TestCase
{
    private ?ObjectManagerInterface $objectManager;
    private ?Product $productEntityExport;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productEntityExport = $this->objectManager->get(Product::class);
    }

    /**
     * @magentoConfigFixture current_store mapp_web_push/general/enable 1
     * @return void
     * @throws LocalizedException
     */
    public function testProductDetailsWithGenderAreIncludedInCSVExport()
    {
        $csvContent = $this->productEntityExport->getCsvContentForExport()[1];

        $this->assertContains('"simple"', $csvContent);
        $this->assertContains('"Simple Product"', $csvContent);
        $this->assertContains('"10.000000"', $csvContent);
        $this->assertContains('"15.000000"', $csvContent);
        $this->assertContains('"100.0000"', $csvContent);
        $this->assertContains('"image.png"', $csvContent);
        $this->assertContains('"small_image.png"', $csvContent);
        $this->assertContains('"Default Category"', $csvContent);
        $this->assertContains('"Description with <b>html tag</b>"', $csvContent);
    }
}
