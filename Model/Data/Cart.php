<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Checkout\Model\Session;

class Cart extends AbstractData
{
    protected Session $checkoutSession;
    protected Product $product;

    public function __construct(
        Session $checkoutSession,
        Product $product
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->product = $product;
    }

    private function generate()
    {
        $productData = $this->checkoutSession->getData('webtrekk_addproduct');

        if ($productData) {
            $this->set('product', $productData);
            $this->checkoutSession->setData('webtrekk_addproduct', null);
        }
    }

    // -----------------------------------------------
    // SETTERS AND GETTERS
    // -----------------------------------------------

    /**
     * @return array
     */
    public function getDataLayer(): array
    {
        $this->generate();
        return $this->_data ?? [];
    }
}
