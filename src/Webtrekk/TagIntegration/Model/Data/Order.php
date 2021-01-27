<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Model\Data;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Webtrekk\TagIntegration\Helper\DataLayer as DataLayerHelper;

class Order extends AbstractData
{

    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var CollectionFactory
     */
    protected $_salesOrderCollection;
    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $_productAttributeRepositoryInterface;

    /**
     * @param Session $checkoutSession
     * @param CollectionFactory $salesOrderCollection
     * @param Product $product
     * @param ProductAttributeRepositoryInterface $productAttributeRepositoryInterface
     */
    public function __construct(Session $checkoutSession, CollectionFactory $salesOrderCollection, Product $product, ProductAttributeRepositoryInterface $productAttributeRepositoryInterface)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_salesOrderCollection = $salesOrderCollection;
        $this->_product = $product;
        $this->_productAttributeRepositoryInterface = $productAttributeRepositoryInterface;
    }

    /**
     * @param array $orderIds
     *
     * @return Collection
     */
    private function getOrderCollection($orderIds)
    {
        $orderCollection = $this->_salesOrderCollection->create();
        $orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);

        return $orderCollection;
    }

    /**
     * @param array[\Magento\Sales\Model\Order\Item] $items
     */
    private function setProducts($items)
    {
        $existingProductData = [];

        foreach ($items as $item) {
            $this->_product->setProduct($item->getProduct());
            $productData = $this->_product->getDataLayer();
            $productData['qty'] = intval($item->getQtyOrdered());
            $productData['quantity'] = intval($item->getQtyOrdered());

            if (!$productData['price']) {
                $productData['price'] = $item->getPrice();
                $productData['cost'] = $item->getPrice();
            }

            $productData['attributes'] = array();
            $allAttributesForProduct = array();
            if($item->getProductType() === 'configurable') {
                $selectedOptions = $item->getProductOptions()['info_buyRequest']['super_attribute'];
                foreach ($selectedOptions as $attributeCodeId => $optionValueId) {
                    $attributeRepo = $this->_productAttributeRepositoryInterface->get($attributeCodeId);
                    $allAttributesForProduct[$attributeRepo->getAttributeCode()] = $attributeRepo->getSource()->getOptionText($optionValueId);
                }
            }
            $productData['attributes'] = $allAttributesForProduct;

            $tmp = DataLayerHelper::merge($existingProductData, $productData);
            $existingProductData = $tmp;
        }

        $existingProductData['status'] = 'conf';
        $this->set('product', $existingProductData);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
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
     * @param Collection $orderCollection
     */
    private function setOrders($orderCollection)
    {
        foreach ($orderCollection as $order) {
            $this->setProducts($order->getAllVisibleItems());
            $this->setTransaction($order);
        }
    }

    private function generate()
    {
        $orderIds = $this->_checkoutSession->getData('webtrekk_order_success');

        if (!empty($orderIds)) {
            $orderCollection = $this->getOrderCollection($orderIds);
            if ($orderCollection) {
                $this->setOrders($orderCollection);
            }

            $this->_checkoutSession->setData('webtrekk_order_success', null);
        }
    }

    /**
     * @return array
     */
    public function getDataLayer()
    {
        $this->generate();

        return $this->_data;
    }
}
