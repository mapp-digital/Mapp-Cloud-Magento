<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Model\Data;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Session;
use Magento\Catalog\Model\ProductRepository;
use Webtrekk\TagIntegration\Helper\DataLayer;

class Product extends AbstractData
{

    /**
     * @var string
     */
    const ATTRIBUTE_SOURCE_TABLE = 'Magento\Eav\Model\Entity\Attribute\Source\Table';

    /**
     * @var Data
     */
    protected $_catalogData;

    /**
     * @var CategoryRepository
     */
    protected $_categoryRepository;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var Session
     */
    protected $_catalogSession;

    /**
     * @var ProductRepository
     */
    protected $_productRepository;

    /**
     * @param Data $catalogData
     * @param CategoryRepository $categoryRepository
     * @param Session $session
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Data $catalogData,
        CategoryRepository $categoryRepository,
        Session $session,
        ProductRepository $productRepository
    )
    {
        $this->_catalogData = $catalogData;
        $this->_categoryRepository = $categoryRepository;
        $this->_catalogSession = $session;
        $this->_productRepository = $productRepository;
    }

    private function setAvailableCategories()
    {
        $categoryIds = $this->_product->getCategoryIds();
        $productAvailableInCategory = [];

        for ($j = 0; $j < count($categoryIds); $j++) {
            $productAvailableInCategory[] = $this->_categoryRepository->get($categoryIds[$j])->getName();
        }

        $this->set('availableInCategory', $productAvailableInCategory);
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $productAttribute
     *
     * @return boolean
     */
    private function canUseAttributeText($productAttribute)
    {
        /**
         * text -> Text Field
         * textarea -> Text Area
         * date -> Date
         * boolean -> Yes/No
         * multiselect -> Multiple Select
         * select -> Dropdown
         * price -> Price
         * media_image -> Media Image
         * gallery -> Gallery
         * weee -> Fixed Product Tax
         * swatch_visual -> Visual Swatch
         * swatch_text -> Text Swatch
         */
        $frontendInput = $productAttribute->getData('frontend_input');
        $sourceModel = $productAttribute->getData('source_model');

        return ((!$sourceModel || $sourceModel == self::ATTRIBUTE_SOURCE_TABLE) && ($frontendInput == 'multiselect' || $frontendInput == 'select'));
    }

    /**
     * @param string $methodeName
     */
    private function getMethodeName($methodeName)
    {
        return ucfirst(implode('', explode('_', ucwords($methodeName, '_'))));
    }

    private function setAttributes()
    {
        $productAttributes = $this->_product->getAttributes();
        foreach ($productAttributes as $productAttribute) {
            $productAttributeCode = $productAttribute->getAttributeCode();
            $productAttributeData = $this->_product->getData($productAttributeCode);
            $frontendInput = $productAttribute->getFrontendInput();

            if ($frontendInput != 'gallery') {
                if ($this->canUseAttributeText($productAttribute) && !is_array($productAttributeData)) {
                    $dataResult = $this->_product->getAttributeText($productAttributeCode);

                    $this->set($productAttributeCode, $dataResult);
                } else {
                    $this->set($productAttributeCode, $productAttributeData);
                }
            }

            $methodeName = 'get' . $this->getMethodeName($productAttributeCode);
            if (empty($this->get($productAttributeCode)) && method_exists($this->_product, $methodeName)) {
                $this->set($productAttributeCode, $this->_product->{$methodeName}());
            }
        }
    }

    private function setBreadcrumb()
    {
        $path = $this->_catalogData->getBreadcrumbPath();
        $counter = 1;

        foreach ($path as $name => $breadcrumb) {
            if (isset($breadcrumb['link'])) {
                $this->set('category' . $counter, $breadcrumb['label']);
                $counter++;
            }
        }
    }

    private function generate()
    {
        $this->setBreadcrumb();

        if (!$this->_product) {
            $productId = $this->_catalogSession->getData('last_viewed_product_id');
            $this->_product = $this->_productRepository->getById($productId);
        }

        if ($this->_product) {
            $this->setAvailableCategories();
            $this->setAttributes();
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     */
    public function setProduct($product)
    {
        if ($product) {
            $this->_product = $product;
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
