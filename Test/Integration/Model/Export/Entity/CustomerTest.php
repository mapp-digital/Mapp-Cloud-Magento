<?php

namespace MappDigital\Cloud\Test\Integration\Model\Export\Client;

use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MappDigital\Cloud\Model\Export\Entity\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    private ?ObjectManagerInterface $objectManager;
    private ?Customer $customerEntityExport;
    private ?CustomerCollectionFactory $customerCollectionFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerEntityExport = $this->objectManager->get(Customer::class);
        $this->customerCollectionFactory = $this->objectManager->get(CustomerCollectionFactory::class);
    }

    /**
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_customer_with_addresses_and_gender.php
     */
    public function testRandomCustomerDetailsWithGenderAreIncludedInCSVExport()
    {
        $csvContent = $this->customerEntityExport->getCsvContentForExport()[1];

        $this->assertContains('"customer_with_gender@test.com"', $csvContent);
        $this->assertContains('"John"', $csvContent);
        $this->assertContains('"Smith"', $csvContent);
        $this->assertContains('"Female"', $csvContent);
        $this->assertContains('"Mr."', $csvContent);
        $this->assertContains('"1234-12-12"', $csvContent);
    }

    /**
     * @magentoDataFixture MappDigital_Cloud::Test/Integration/_files/mapp_customer_with_addresses_no_gender.php
     */
    public function testRandomCustomerDetailsWithoutGenderAreIncludedInCSVExport()
    {
        $csvContent = $this->customerEntityExport->getCsvContentForExport()[1];

        $this->assertContains('"customer_with_no_gender@test.com"', $csvContent);
        $this->assertContains('"John"', $csvContent);
        $this->assertContains('"Smith"', $csvContent);
        $this->assertContains('"Unknown"', $csvContent);
        $this->assertContains('"Mr."', $csvContent);
        $this->assertContains('"1234-12-12"', $csvContent);
    }
}
