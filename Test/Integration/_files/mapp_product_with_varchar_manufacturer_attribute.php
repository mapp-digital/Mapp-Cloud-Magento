<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('MappDigital_Cloud::Test/Integration/_files/mapp_product_varchar_manufacturer_attribute.php');
Resolver::getInstance()->requireDataFixture('MappDigital_Cloud::Test/Integration/_files/mapp_product_simple.php');

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->create(ProductAttributeRepositoryInterface::class);
$product = $productRepository->get('simple');
/** @var Config $eavConfig */
$eavConfig = $objectManager->get(Config::class);
$eavConfig->clear();
$attribute = $eavConfig->getAttribute(Product::ENTITY, 'manufacturer');
$attribute->setDefaultValue('Manufacturer');
$attributeRepository->save($attribute);

$product->setManufacturer('manufacturer', $attribute->getDefaultValue());
$productRepository->save($product);
