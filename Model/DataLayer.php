<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model;

use Magento\Framework\App\Action\Context;
use Magento\Framework\DataObject;
use MappDigital\Cloud\Model\Data\Cart;
use MappDigital\Cloud\Model\Data\Customer;
use MappDigital\Cloud\Model\Data\Order;
use MappDigital\Cloud\Model\Data\Page;
use MappDigital\Cloud\Model\Data\Product;
use MappDigital\Cloud\Model\Data\Wishlist;

class DataLayer extends DataObject
{
    protected Context $context;
    protected Product $product;
    protected Page $page;
    protected Customer $customer;
    protected Cart $cart;
    protected Order $order;
    protected Wishlist $wishlist;
    protected array $_variables = [];
    protected string $fullActionName = '';

    public function __construct(
        Context $context,
        Product $product,
        Page $page,
        Customer $customer,
        Cart $cart,
        Order $order,
        Wishlist $wishlist
    ) {
        $this->context = $context;
        $this->product = $product;
        $this->page = $page;
        $this->customer = $customer;
        $this->cart = $cart;
        $this->order = $order;
        $this->wishlist = $wishlist;

        $this->fullActionName = $this->context->getRequest()->getFullActionName() ?? '';
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

    // -----------------------------------------------
    // SETTERS AND GETTERS
    // -----------------------------------------------

    public function setPageDataLayer()
    {
        $this->_addArray('page', $this->page->getDataLayer());
    }

    public function setProductDataLayer($productUrlFragment)
    {
        $productDataLayer = $this->product->getDataLayer($productUrlFragment);

        if ($this->fullActionName === 'catalog_product_view') {
            $productDataLayer['quantity'] = '1';
            $productDataLayer['status'] = 'view';
        }

        $this->_addArray('product', $productDataLayer);
    }

    public function setCustomerDataLayer()
    {
        $this->_addArray('customer', $this->customer->getDataLayer());
    }

    public function setCartDataLayer()
    {
        $this->_addArray('add', $this->cart->getDataLayer());
    }

    public function setWishlistData()
    {
        $this->_addArray('add', $this->wishlist->getDataLayer());
    }

    public function setOrderDataLayer()
    {
        $orderData = $this->order->getDataLayer();

        if (!empty($orderData)) {
            $this->_addArray('product', $orderData['product']);
            $this->_addArray('order', $orderData['order']);
        }
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->_variables;
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

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
}