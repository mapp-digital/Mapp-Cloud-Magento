<?php
namespace MappDigital\Cloud\Model\Export\Entity;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\Framework\Filesystem as MagentoFileSystemManager;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Export\Client\FileSystem as MappFilesystemExport;
use MappDigital\Cloud\Model\Export\Client\Sftp;


class Product extends ExportAbstract
{
    const EXPORT_FILE_PREFIX = 'catalog_product_';

    const ATTRIBUTES_FOR_EXPORT = [
        'sku',
        'name',
        'price',
        'url_key',
        'image',
        'small_image',
        'manufacturer',
        'msrp',
        'description',
    ];

    const ALL_COLUMNS_IN_ORDER = [
        'sku',
        'name',
        'price',
        'url_key',
        'qty',
        'image',
        'small_image',
        'manufacturer',
        'category',
        'msrp',
        'description'
    ];

    protected ProductCollectionFactory $productCollectionFactory;
    protected AddStockDataToCollectionWithAllColumns $addStockDataToCollection;
    protected GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite;
    protected CategoryRepository $categoryRepository;

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        AddStockDataToCollectionWithAllColumns $addStockDataToCollection,
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        MagentoFileSystemManager $magentoFilesystemManager,
        Sftp $sftp,
        MappFilesystemExport $mappFilesystemExport
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->addStockDataToCollection = $addStockDataToCollection;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->categoryRepository = $categoryRepository;

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
            $productAvailableInCategory = [];
            foreach ($entity->getCategoryIds() ?? [] as $categoryId) {
                try {
                    $productAvailableInCategory[] = $this->categoryRepository->get($categoryId)->getName();
                } catch (NoSuchEntityException $exception) {}
            }

            $entity->setData('category', implode(', ', $productAvailableInCategory));

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
     * @return ProductCollection
     */
    public function getEntitiesForExport()
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect(self::ATTRIBUTES_FOR_EXPORT, 'left');
        $this->addStockDataToCollection->execute($productCollection, false, $this->getStockIdForCurrentWebsite->execute());
        $productCollection->addCategoryIds();

        return $productCollection;
    }
}
