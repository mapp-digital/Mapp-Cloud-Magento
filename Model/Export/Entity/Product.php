<?php
namespace MappDigital\Cloud\Model\Export\Entity;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\Framework\Filesystem as MagentoFileSystemManager;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Connect\Catalog\Product\Consumer;
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
        'sku' => 'productSKU',
        'name' => 'productName',
        'price' => 'productPrice',
        'url' => 'productURL',
        'qty' => 'qty',
        'image' => 'imageURL',
        'small_image' => 'zoomImageURL',
        'manufacturer' => 'brand',
        'category' => 'category',
        'msrp' =>  'msrp',
        'description' => 'description'
    ];

    public function __construct(
        private ProductCollectionFactory $productCollectionFactory,
        private AddStockDataToCollectionWithAllColumns $addStockDataToCollection,
        private GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        private CategoryRepository $categoryRepository,
        Consumer $productConsumer,
        StoreManagerInterface $storeManager,
        MagentoFileSystemManager $magentoFilesystemManager,
        Sftp $sftp,
        MappFilesystemExport $mappFilesystemExport
    )
    {
        parent::__construct($storeManager, $magentoFilesystemManager, $sftp, $mappFilesystemExport, $productConsumer);
    }

    /**
     * @throws LocalizedException
     */
    public function getCsvContentForExport(): array
    {
        $entities = $this->getEntitiesForExport();
        $data = [];

        foreach ($this::ALL_COLUMNS_IN_ORDER as $dataKey => $columnName) {
            $data[] = '"' . $columnName . '"';
        }

        $rows[] = $data ?? [];

        while ($entity = $entities->fetchItem()) {
            /** @var \Magento\Catalog\Model\Product $entity */
            $data = [];
            $productAvailableInCategory = [];
            foreach ($entity->getCategoryIds() ?? [] as $categoryId) {
                try {
                    $productAvailableInCategory[] = $this->categoryRepository->get($categoryId)->getName();
                } catch (NoSuchEntityException) {}
            }

            $entity->setData('category', implode(', ', $productAvailableInCategory));

            foreach ($this::ALL_COLUMNS_IN_ORDER as $dataKey => $columnName) {
                if (str_contains($dataKey, 'image')) {
                    $dataEntry = $this->getFullPathForImage($entity->getData($dataKey));
                } elseif ($dataKey === 'price') {
                    $dataEntry =  $entity->getPrice() ?: $entity->getMinimalPrice() ?? $entity->getFinalPrice();
                } elseif ($dataKey === 'url') {
                    $dataEntry = $entity->getProductUrl();
                } else {
                    $dataEntry = $entity->getData($dataKey);
                }

                $data[] = '"' . str_replace(
                        ['"', '\\'],
                        ['""', '\\\\'],
                        $dataEntry ?: ''
                    ) . '"';
            }

            $rows[] = $data;
        }

        return $rows ?? [];
    }

    /**
     * @return ProductCollection
     */
    public function getEntitiesForExport(): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect(self::ATTRIBUTES_FOR_EXPORT, 'left');
        $this->addStockDataToCollection->execute($productCollection, false, $this->getStockIdForCurrentWebsite->execute());
        $productCollection->addCategoryIds();

        return $productCollection;
    }
}
