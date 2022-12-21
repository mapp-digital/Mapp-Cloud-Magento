<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2021 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\CatalogUrlRewrite\Model\ResourceModel\Category\Product as ProductUrlRewriteResource;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Session;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Catalog\Model\Product as CatalogProductModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Product extends AbstractData
{
    /**
     * @var string
     */
    const ATTRIBUTE_SOURCE_TABLE = 'Magento\Eav\Model\Entity\Attribute\Source\Table';

    protected Data $catalogData;
    protected CategoryRepository $categoryRepository;
    protected Session $catalogSession;
    protected ProductRepository $productRepository;
    protected ProductUrlRewriteResource $productUrlRewriteResource;
    protected LoggerInterface $logger;
    protected ?CatalogProductModel $product = null;

    public function __construct(
        Data $catalogData,
        CategoryRepository $categoryRepository,
        Session $session,
        ProductRepository $productRepository,
        ProductUrlRewriteResource $productUrlRewriteResource,
        LoggerInterface $logger
    )
    {
        $this->catalogData = $catalogData;
        $this->categoryRepository = $categoryRepository;
        $this->catalogSession = $session;
        $this->productRepository = $productRepository;
        $this->productUrlRewriteResource = $productUrlRewriteResource;
        $this->logger = $logger;
    }

    private function generate($productUrlFragment)
    {
        $this->setBreadcrumb();

        if (!$this->product) {
            $productId = $this->catalogSession->getData('last_viewedproduct_id');
            if (is_null($productId)) {
                $productId = $this->fallbackProductIdGetter($productUrlFragment);
            }
            if (!is_null($productId)) {
                $this->product = $this->productRepository->getById($productId);
            }
        }

        if ($this->product) {
            $this->setAvailableCategories();
            $this->setAttributes();
        }
    }

    // -----------------------------------------------
    // SETTERS AND GETTERS
    // -----------------------------------------------

    /**
     * @return void
     */
    private function setAvailableCategories()
    {
        $categoryIds = $this->product->getCategoryIds();
        $productAvailableInCategory = [];

        foreach ($categoryIds as $categoryId) {
            try {
                $productAvailableInCategory[] = $this->categoryRepository->get($categoryId)->getName();
            } catch (NoSuchEntityException $exception) {
                $this->logger->error($exception);
            }

        }

        $this->set('availableInCategory', $productAvailableInCategory);
    }

    /**
     * @param AbstractAttribute $productAttribute
     *
     * @return boolean
     */
    private function canUseAttributeText($productAttribute): bool
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
     * @return string
     */
    private function getMethodeName(string $methodeName): string
    {
        return ucfirst(implode('', explode('_', ucwords($methodeName, '_')))) ?? '';
    }

    private function setAttributes()
    {
        $productAttributes = $this->product->getAttributes();
        foreach ($productAttributes as $productAttribute) {
            $productAttributeCode = $productAttribute->getAttributeCode();
            $productAttributeData = $this->product->getData($productAttributeCode);
            $frontendInput = $productAttribute->getFrontendInput();

            if ($frontendInput != 'gallery') {
                if ($this->canUseAttributeText($productAttribute) && !is_array($productAttributeData)) {
                    $dataResult = $this->product->getAttributeText($productAttributeCode);
                    $this->set($productAttributeCode, $dataResult);
                } else {
                    $this->set($productAttributeCode, $productAttributeData);
                }
            }

            $methodeName = 'get' . $this->getMethodeName($productAttributeCode);
            if (empty($this->get($productAttributeCode)) && method_exists($this->product, $methodeName)) {
                $this->set($productAttributeCode, $this->product->{$methodeName}());
            }
        }
    }

    private function setBreadcrumb()
    {
        $path = $this->catalogData->getBreadcrumbPath();
        $counter = 1;

        foreach ($path as $name => $breadcrumb) {
            if (isset($breadcrumb['link'])) {
                $this->set('category' . $counter, $breadcrumb['label']);
                $counter++;
            }
        }
    }

    /**
     * @param CatalogProductModel $product
     */
    public function setProduct(CatalogProductModel $product)
    {
        if ($product->hasData()) {
            $this->product = $product;
        }
    }

    /**
     * @return CatalogProductModel|null
     */
    public function getProduct(): ?CatalogProductModel
    {
        return $this->product;
    }

    /**
     * @param $productUrlFragment
     * @return array
     */
    public function getDataLayer($productUrlFragment): array
    {
        $this->generate($productUrlFragment);
        return $this->_data ?? [];
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

    private function fallbackProductIdGetter($productUrlFragment)
    {
        $connection = $this->productUrlRewriteResource->getConnection();
        $table = $this->productUrlRewriteResource->getTable('url_rewrite');
        $select = $connection->select();
        $select->from($table, ['entity_id'])
            ->where('entity_type = :entity_type')
            ->where('request_path LIKE :request_path');

        $result = $connection->fetchCol(
            $select,
            ['entity_type' => 'product', 'request_path' => $productUrlFragment]
        );

        return $result[0] ?? null;
    }
}
