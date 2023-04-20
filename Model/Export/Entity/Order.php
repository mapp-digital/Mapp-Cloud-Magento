<?php
namespace MappDigital\Cloud\Model\Export\Entity;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem as MagentoFileSystemManager;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Export\Client\FileSystem as MappFilesystemExport;
use MappDigital\Cloud\Model\Export\Client\Sftp;

class Order extends ExportAbstract
{
    const EXPORT_FILE_PREFIX = 'sales_order_item_';

    const ATTRIBUTES_FOR_EXPORT = [
        'order_id',
        'created_at',
        'sku',
        'name',
        'price',
        'qty_ordered',
        'qty_refunded',
        'store_id',
        'discount_amount',
        'discount_percent'
    ];

    const ORDER_ATTRIBUTES_FOR_EXPORT = [
        'base_currency_code',
        'increment_id'
    ];

    const PRODUCT_ATTRIBUTES_FOR_EXPORT = [
        'image',
        'small_image'
    ];

    const ALL_COLUMNS_IN_ORDER = [
        'order_id',
        'increment_id',
        'created_at',
        'sku',
        'name',
        'price',
        'qty_ordered',
        'qty_refunded',
        'store_id',
        'discount_amount',
        'discount_percent',
        'base_currency_code',
        'image',
        'small_image'
    ];

    protected bool $shouldIncludeImages = true;

    public function __construct(
        protected OrderItemCollectionFactory $orderItemCollectionFactory,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected AttributeRepositoryInterface $attributeRepository,
        StoreManagerInterface $storeManager,
        MagentoFileSystemManager $magentoFilesystemManager,
        Sftp $sftp,
        MappFilesystemExport $mappFilesystemExport
    )
    {
        parent::__construct($storeManager, $magentoFilesystemManager, $sftp, $mappFilesystemExport);
    }

    /**
     * @throws LocalizedException
     */
    public function getCsvContentForExport(): array
    {
        $entities = $this->getEntitiesForExport();
        $data = [];

        foreach ($this::ALL_COLUMNS_IN_ORDER as $column) {
            $data[] = '"' . $column . '"';
        }

        $rows[] = $data ?? [];

        while ($entity = $entities->fetchItem()) {
            $data = [];
            foreach ($this::ALL_COLUMNS_IN_ORDER as $column) {
                $data[] = '"' . str_replace(
                        ['"', '\\'],
                        ['""', '\\\\'],
                        $entity->getData($column) ?: ''
                    ) . '"';
            }

            $rows[] = $data;
        }

        return $rows ?? [];
    }

    /**
     * @return OrderItemCollection
     * @throws LocalizedException
     */
    public function getEntitiesForExport(): OrderItemCollection
    {
        $orderItemCollection = $this->orderItemCollectionFactory->create();
        $orderItemCollection->addFieldToSelect(self::ATTRIBUTES_FOR_EXPORT);
        $orderItemCollection->join(
            ['so' => $orderItemCollection->getResource()->getTable('sales_order')],
            'main_table.order_id = so.entity_id',
            ['base_currency_code', 'increment_id']
        );

        if ($this->shouldIncludeImages) {
            foreach ($this->getAdditionalAttributes() as $attribute) {
                $orderItemCollection->join(
                    ['ca' . $attribute->getAttributeCode() => 'catalog_product_entity_' . $attribute->getBackendType()],
                    'main_table.product_id = ca' . $attribute->getAttributeCode() . '.row_id and ca' . $attribute->getAttributeCode() . '.attribute_id = ' . $attribute->getAttributeId(),
                    ['value as ' . $attribute->getAttributeCode()]
                );
            }
        }

        return $orderItemCollection;
    }

    /**
     * @return array
     */
    private function getAdditionalAttributes(): array
    {
        $attributeSearchCriteria = $this->searchCriteriaBuilder->addFilter(
            'attribute_code',
            self::PRODUCT_ATTRIBUTES_FOR_EXPORT,
            'in'
        )->create();

        return $this->attributeRepository->getList(
            ProductAttributeInterface::ENTITY_TYPE_CODE,
            $attributeSearchCriteria
        )->getItems() ?? [];
    }

    /**
     * @param bool $enabled
     * @return void
     */
    public function setShouldIncludeImages(bool $enabled)
    {
        $this->shouldIncludeImages = $enabled;
    }
}
