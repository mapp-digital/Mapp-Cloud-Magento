<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
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

class DataLayer extends DataObject
{
    protected Context $_context;
    protected Product $_product;
    protected Page $_page;
    protected Customer $_customer;
    protected Cart $_cart;
    protected Order $_order;
    protected array $_variables = [];
    protected string $fullActionName = '';

    public function __construct(
        Context $context,
        Product $product,
        Page $page,
        Customer $customer,
        Cart $cart,
        Order $order
    ) {
        $this->_context = $context;
        $this->_product = $product;
        $this->_page = $page;
        $this->_customer = $customer;
        $this->_cart = $cart;
        $this->_order = $order;

        $this->fullActionName = $this->_context->getRequest()->getFullActionName() ?? '';
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
        $this->_addArray('page', $this->_page->getDataLayer());
    }

    public function setProductDataLayer($productUrlFragment)
    {
        $productDataLayer = $this->_product->getDataLayer($productUrlFragment);

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
