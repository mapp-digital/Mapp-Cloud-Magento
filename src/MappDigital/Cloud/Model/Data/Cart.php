<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Checkout\Model\Session;

class Cart extends AbstractData
{
    public function __construct(
        protected Session $checkoutSession,
        protected Product $product
    ) {}

    private function generate()
    {
        $productData = $this->checkoutSession->getData('webtrekk_addproduct')
            ?? $this->checkoutSession->getData('webtrekk_removeproduct')
            ?? '';

        if ($productData) {
            $this->set('product', $productData);
            if ($this->checkoutSession->getData('webtrekk_removeproduct')) {
                $this->checkoutSession->setData('webtrekk_removeproduct', null);
            }
            if ($this->checkoutSession->getData('webtrekk_addproduct')) {
                $this->checkoutSession->setData('webtrekk_addproduct', null);
            }
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
