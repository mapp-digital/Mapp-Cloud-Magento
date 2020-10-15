<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Model\Data;

use Magento\Checkout\Model\Session;

class Cart extends AbstractData
{

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @param Session $checkoutSession
     * @param Product $product
     */
    public function __construct(Session $checkoutSession, Product $product)
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_product = $product;
    }

    private function generate()
    {
        $productData = $this->_checkoutSession->getData('webtrekk_add_product');
        if ($productData) {
            $this->set('product', $productData);

            $this->_checkoutSession->setData('webtrekk_add_product', null);
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
