<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Checkout\Model\Session;

class Wishlist extends AbstractData
{

    public function __construct(
        protected Session $checkoutSession,
        protected Product $product
    ) {}

    /**
     * @return void
     */
    private function generate()
    {
        $wishlistData = $this->checkoutSession->getData('webtrekk_addtowishlist') ?? '';

        if ($wishlistData) {
            $this->set('wishlist', $wishlistData);
            $this->checkoutSession->setData('webtrekk_addtowishlist', null);
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
