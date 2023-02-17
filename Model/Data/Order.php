<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Order as MagentoOrderModel;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use Psr\Log\LoggerInterface;

class Order extends AbstractData
{
    protected Session $checkoutSession;
    protected CollectionFactory $salesOrderCollection;
    protected Product $product;
    protected ProductAttributeRepositoryInterface $productAttributeRepositoryInterface;
    protected CombinedLogger $mappCombinedLogger;

    public function __construct(
        Session $checkoutSession,
        CollectionFactory $salesOrderCollection,
        Product $product,
        ProductAttributeRepositoryInterface $productAttributeRepositoryInterface,
        CombinedLogger $mappCombinedLogger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->salesOrderCollection = $salesOrderCollection;
        $this->product = $product;
        $this->productAttributeRepositoryInterface = $productAttributeRepositoryInterface;
        $this->mappCombinedLogger = $mappCombinedLogger;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function generate()
    {
        $orderIds = $this->checkoutSession->getData('webtrekk_order_success');

        if (!empty($orderIds)) {
            $orderCollection = $this->getOrderCollection($orderIds);
            if ($orderCollection->getSize()) {
                $this->setOrders($orderCollection);
            }

            $this->checkoutSession->setData('webtrekk_order_success', null);
        }
    }

    // -----------------------------------------------
    // SETTERS AND GETTERS
    // -----------------------------------------------

    /**
     * @param array[\Magento\Sales\Model\Order\Item] $items
     * @throws NoSuchEntityException
     */
    private function setProducts($items)
    {
        $existingProductData = [];

        foreach ($items as $item) {
            try {
                $product = $item->getProduct();
                $urlFragment = DataLayerHelper::getUrlFragment($product);
                $this->product->setProduct($product);
                $productData = $this->product->getDataLayer($urlFragment);
                $productData['qty'] = intval($item->getQtyOrdered());
                $productData['quantity'] = intval($item->getQtyOrdered());

                $productData['price'] = $item->getPrice();
                $productData['cost'] = $item->getPrice();

                $productData['attributes'] = array();
                $allAttributesForProduct = array();
                if($item->getProductType() === 'configurable') {
                    $selectedOptions = $item->getProductOptions()['info_buyRequest']['super_attribute'];
                    foreach ($selectedOptions as $attributeCodeId => $optionValueId) {
                        $attributeRepo = $this->productAttributeRepositoryInterface->get($attributeCodeId);
                        $allAttributesForProduct[$attributeRepo->getAttributeCode()] = $attributeRepo->getSource()->getOptionText($optionValueId);
                    }
                }
                $productData['attributes'] = $allAttributesForProduct;

                $tmp = DataLayerHelper::merge($existingProductData, $productData);
                $existingProductData = $tmp;
            } catch (NoSuchEntityException $exception) {
                $this->mappCombinedLogger->error(sprintf('Mapp Connect: -- ERROR -- Sending setting datalayer products: %s', $exception->getMessage()), __CLASS__, __FUNCTION__, ['exception' => $exception]);
                $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__,__FUNCTION__);
            }
        }

        $existingProductData['status'] = 'conf';
        $this->set('product', $existingProductData);
    }

    /**
     * @param MagentoOrderModel $order
     */
    private function setTransaction($order)
    {
        $transaction = [
            'shoppingCartStatus' => 'conf',
            'id' => $order->getIncrementId(),
            'value' => $order->getGrandTotal(),
            'totalDue' => $order->getTotalDue(),
            'totalItemCount' => $order->getTotalItemCount(),
            'currency' => $order->getOrderCurrencyCode(),
            'weight' => $order->getWeight(),
            'couponCode' => $order->getCouponCode(),
            'giftMessageId' => $order->getGiftMessageId(),
            'discountAmount' => $order->getDiscountAmount(),
            'discountAmountTaxCompensation' => $order->getDiscountTaxCompensationAmount(),
            'shippingMethod' => $order->getShippingMethod(),
            'shippingDescription' => $order->getShippingDescription(),
            'shippingAmount' => $order->getShippingAmount(),
            'shippingAmountDiscount' => $order->getShippingDiscountAmount(),
            'shippingAmountDiscountTaxCompensation' => $order->getShippingDiscountTaxCompensationAmount(),
            'shippingAmountInclTax' => $order->getShippingInclTax(),
            'shippingAmountTax' => $order->getShippingTaxAmount(),
            'subtotal' => $order->getSubtotal(),
            'subtotalInclTax' => $order->getSubtotalInclTax(),
            'taxAmount' => $order->getTaxAmount()
        ];

        if (!is_null($order->getPayment())) {
            $transaction['payment'] = $order->getPayment()->getData();
        }

        if (!is_null($order->getBillingAddress())) {
            $transaction['billing'] = $order->getBillingAddress()->getData();
        }

        if (!is_null($order->getShippingAddress())) {
            $transaction['shipping'] = $order->getShippingAddress()->getData();
        }

        $this->set('order', $transaction);
    }

    /**
     * @param $orderCollection
     * @return void
     * @throws NoSuchEntityException
     */
    private function setOrders($orderCollection)
    {
        foreach ($orderCollection as $order) {
            $this->setProducts($order->getAllVisibleItems());
            $this->setTransaction($order);
        }
    }

    /**
     * @param array $orderIds
     *
     * @return Collection
     */
    private function getOrderCollection($orderIds): Collection
    {
        $orderCollection = $this->salesOrderCollection->create();
        $orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        return $orderCollection;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getDataLayer(): array
    {
        $this->generate();
        return $this->_data ?? [];
    }
}
