<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Model\Data\Product;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\App\Request\Http;

class TIDatalayerAddToCart implements ObserverInterface
{

    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var Config
     */
    protected $_config;
    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $_productAttributeRepositoryInterface;

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @param Session $checkoutSession
     * @param Config $_config
     * @param Product $product
     * @param ProductAttributeRepositoryInterface $productAttributeRepositoryInterface
     * @param Http $request
     */
    public function __construct(
        Session $checkoutSession,
        Config $config,
        Product $product,
        ProductAttributeRepositoryInterface $productAttributeRepositoryInterface,
        Http $request
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_config = $config;
        $this->_product = $product;
        $this->_productAttributeRepositoryInterface = $productAttributeRepositoryInterface;
        $this->_request = $request;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->_config->isEnabled()) {
            $item = $observer->getEvent()->getData('quote_item');
            $product = $observer->getEvent()->getData('product');

            if ($product) {
                $this->_product->setProduct($product);
                $productData = $this->_product->getDataLayer();
                $productData['qty'] = intval($item->getQtyToAdd());
                $productData['quantity'] = intval($item->getQtyToAdd());
                $productData['status'] = 'add';

                $productData['attributes'] = array();
                $allAttributesForProduct = array();
                if($item->getProductType() === 'configurable') {
                    $selectedOptions = json_decode($item->getOptionsByCode()['attributes']->getValue(), true);
                    foreach ($selectedOptions as $attributeCodeId => $optionValueId) {
                        $attributeRepo = $this->_productAttributeRepositoryInterface->get($attributeCodeId);
                        $allAttributesForProduct[$attributeRepo->getAttributeCode()] = $attributeRepo->getSource()->getOptionText($optionValueId);
                    }
                }
                $productData['attributes'] = $allAttributesForProduct;
                $productData['price'] = $item->getPrice();
                $productData['cost'] = $item->getPrice();
                $productData['sku'] = $item->getSku();
                $productData['name'] = $item->getName();
                $productData['weight'] = $item->getWeight();

                $existingProductData = $this->_checkoutSession->getData('webtrekk_add_product');
                if (!$existingProductData) {
                    $existingProductData = [];
                }
                $productDataMerge = DataLayerHelper::merge($existingProductData, $productData);
                $this->_checkoutSession->setData('webtrekk_add_product', $productDataMerge);
            }
        }
    }
}
