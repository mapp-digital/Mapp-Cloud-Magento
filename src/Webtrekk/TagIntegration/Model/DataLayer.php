<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Model;

use Magento\Framework\App\Action\Context;
use Magento\Framework\DataObject;
use Webtrekk\TagIntegration\Model\Data\Cart;
use Webtrekk\TagIntegration\Model\Data\Customer;
use Webtrekk\TagIntegration\Model\Data\Order;
use Webtrekk\TagIntegration\Model\Data\Page;
use Webtrekk\TagIntegration\Model\Data\Product;

class DataLayer extends DataObject
{

    /**
     * @var Context
     */
    protected $_context;
    /**
     * @var Product
     */
    protected $_product;
    /**
     * @var Page
     */
    protected $_page;
    /**
     * @var Customer
     */
    protected $_customer;
    /**
     * @var Cart
     */
    protected $_cart;
    /**
     * @var Order
     */
    protected $_order;
    /**
     * @var array
     */
    protected $_variables = [];
    /**
     * @var string
     */
    protected $_fullActionName;

    /**
     * @param Context $context
     * @param Product $product
     * @param Page $page
     * @param Customer $customer
     * @param Cart $cart
     * @param Order $order
     */
    public function __construct(Context $context, Product $product, Page $page, Customer $customer, Cart $cart, Order $order)
    {
        $this->_context = $context;
        $this->_product = $product;
        $this->_page = $page;
        $this->_customer = $customer;
        $this->_cart = $cart;
        $this->_order = $order;

        $this->fullActionName = $this->_context->getRequest()->getFullActionName();
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->_variables;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addVariable($name, $value)
    {
        if (!empty($name)) {
            if (is_object($value) || is_array($value)) {
                $this->_variables[$name] = $value;
            } else {
                $this->_variables[$name] = '' . $value;
            }
        }
    }

    /**
     * @param string $prefix
     * @param mixed $data
     */
    private function _addArray($prefix = "", $data = [])
    {
        if (is_object($data) || is_array($data)) {
            foreach ($data as $key => $value) {
                $suffix = ucfirst(implode('', explode('_', ucwords($key, '_'))));
                if (is_object($value) || is_array($value)) {
                    if (count($value) > 0 && isset($value[0])) {
                        $this->addVariable($prefix . $suffix, $value);
                    } else {
                        $this->_addArray($prefix . $suffix, $value);
                    }
                } else {
                    $this->addVariable($prefix . $suffix, $value);
                }
            }
        } else {
            $this->addVariable($prefix, $data);
        }
    }

    public function setPageDataLayer()
    {
        $this->_addArray('page', $this->_page->getDataLayer());
    }

    public function setProductDataLayer()
    {
        $productDataLayer = $this->_product->getDataLayer();

        if ($this->fullActionName === 'catalog_product_view') {
            $productDataLayer['quantity'] = '1';
            $productDataLayer['status'] = 'view';
        }

        $this->_addArray('product', $productDataLayer);
    }

    public function setCustomerDataLayer()
    {
        $this->_addArray('customer', $this->_customer->getDataLayer());
    }

    public function setCartDataLayer()
    {
        $this->_addArray('add', $this->_cart->getDataLayer());
    }

    public function setOrderDataLayer()
    {
        $orderData = $this->_order->getDataLayer();

        if (!empty($orderData)) {
            $this->_addArray('product', $orderData['product']);
            $this->_addArray('order', $orderData['order']);
        }
    }
}
