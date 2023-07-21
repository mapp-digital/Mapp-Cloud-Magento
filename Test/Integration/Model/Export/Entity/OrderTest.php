<?php

namespace MappDigital\Cloud\Test\Integration\Model\Export\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MappDigital\Cloud\Model\Export\Entity\Order;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_order_complete.php
 * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_product_simple.php
 */
class OrderTest extends TestCase
{
    private ?ObjectManagerInterface $objectManager;
    private ?Order $orderEntityExport;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderEntityExport = $this->objectManager->get(Order::class);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testOrderDetailsInCSVExport()
    {
        $this->orderEntityExport->setShouldIncludeImages(false);
        $csvContent = $this->orderEntityExport->getCsvContentForExport()[1];

        $this->assertContains('"100000333"', $csvContent);
        $this->assertContains('"simple"', $csvContent);
        $this->assertContains('"Simple Product"', $csvContent);
        $this->assertContains('"90"', $csvContent);
        $this->assertContains('"50.0000"', $csvContent);
        $this->assertContains('"10.0000"', $csvContent);
        $this->assertContains('"1"', $csvContent);
        $this->assertContains('"10.0000"', $csvContent);
        $this->assertContains('"GBP"', $csvContent);
    }
}
