<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
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
use MappDigital\Cloud\Logger\CombinedLogger;
use Psr\Log\LoggerInterface;

class Product extends AbstractData
{
    /**
     * @var string
     */
    const ATTRIBUTE_SOURCE_TABLE = 'Magento\Eav\Model\Entity\Attribute\Source\Table';

    protected ?CatalogProductModel $product = null;

    public function __construct(
        protected Data $catalogData,
        protected CategoryRepository $categoryRepository,
        protected Session $catalogSession,
        protected ProductRepository $productRepository,
        protected ProductUrlRewriteResource $productUrlRewriteResource,
        protected CombinedLogger $mappCombinedLogger
    ) {}

    private function generate($productId)
    {
        $this->setBreadcrumb();

        if (!$this->product) {
            try {
                $this->product = $this->productRepository->getById($productId);
            } catch (NoSuchEntityException) {}
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
                $this->mappCombinedLogger->error(sprintf('Mapp Connect: -- ERROR -- Category Not Available: %s', $exception->getMessage()), __CLASS__, __FUNCTION__, ['exception' => $exception]);
                $this->mappCombinedLogger->error($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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
     * @param $productId
     * @return array
     */
    public function getDataLayer($productId): array
    {
        $this->generate($productId);
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
