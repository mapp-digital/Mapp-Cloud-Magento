<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DB\Transaction;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/default_rollback.php');
Resolver::getInstance()->requireDataFixture('MappDigital_Cloud::Test/Integration/_files/mapp_product_simple.php');

$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productRepository->cleanCache();
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
/** @var InvoiceManagementInterface $invoiceService */
$invoiceService = $objectManager->get(InvoiceManagementInterface::class);
/** @var ShipmentFactory $shipmentFactory */
$shipmentFactory = $objectManager->get(ShipmentFactory::class);
$addressData = [
    AddressInterface::REGION => 'CA',
    AddressInterface::REGION_ID => '12',
    AddressInterface::POSTCODE => '11111',
    AddressInterface::LASTNAME => 'lastname',
    AddressInterface::FIRSTNAME => 'firstname',
    AddressInterface::STREET => 'street',
    AddressInterface::CITY => 'Los Angeles',
    CustomerInterface::EMAIL => 'admin@example.com',
    AddressInterface::TELEPHONE => '11111111',
    AddressInterface::COUNTRY_ID => 'US',
];
$product = $productRepository->get('simple');
/** @var AddressFactory $addressFactory */
$addressFactory = $objectManager->get(AddressFactory::class);
$billingAddress = $addressFactory->create(['data' => $addressData]);
$billingAddress->setAddressType(Address::TYPE_BILLING);
$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType(Address::TYPE_SHIPPING);
/** @var OrderPaymentInterfaceFactory $paymentFactory */
$paymentFactory = $objectManager->get(OrderPaymentInterfaceFactory::class);
$payment = $paymentFactory->create();
$payment->setMethod(Checkmo::PAYMENT_METHOD_CHECKMO_CODE)
    ->setAdditionalInformation('last_trans_id', '11122')
    ->setAdditionalInformation('metadata', ['type' => 'free', 'fraudulent' => false]);
/** @var OrderItemInterface $orderItem */
$orderItem = $objectManager->get(OrderItemInterfaceFactory::class)->create();
$orderItem->setProductId($product->getId())
    ->setQtyOrdered(50)
    ->setQtyRefunded(10)
    ->setBasePrice(100)
    ->setPrice(90)
    ->setRowTotal(90)
    ->setSku($product->getSku())
    ->setName($product->getName())
    ->setStoreId(1)
    ->setProductType('simple')
    ->setDiscountAmount(10)
    ->setDiscountPercent(10)
    ->setBaseRowTotal(90)
    ->setBaseDiscountAmount(10)
    ->setTaxAmount(20)
    ->setBaseTaxAmount(20);

/** @var  OrderInterface $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create();
$order->setIncrementId('100000333')
    ->setCustomerIsGuest(false)
    ->setBillingAddress($billingAddress)
    ->setShippingAddress($shippingAddress)
    ->addItem($orderItem)
    ->setPayment($payment)
    ->setStoreId($storeManager->getStore('default')->getId());

$baseOrderData = include __DIR__ . '/mapp_order_data_key_and_value_base.php';
$baseOrderCustomerData = include __DIR__ . '/mapp_order_customer_data_key_and_value_base.php';

foreach ($baseOrderData as $key => $value) {
    $order->setData($key, $value);
}

foreach ($baseOrderCustomerData as $key => $value) {
    $order->setData($key, $value);
}

$orderRepository->save($order);

$invoice = $invoiceService->prepareInvoice($order);
$invoice->register();
$invoice->setIncrementId($order->getIncrementId());
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = $objectManager->create(Transaction::class);
$transactionSave->addObject($invoice)->addObject($order)->save();

$items = [];
foreach ($order->getItems() as $item) {
    $items[$item->getId()] = $item->getQtyOrdered();
}

$shipment = $objectManager->get(ShipmentFactory::class)->create($order, $items);
$shipment->register();
$shipment->setIncrementId($order->getIncrementId());
$transactionSave = $objectManager->create(Transaction::class);
$transactionSave->addObject($shipment)->addObject($order)->save();
