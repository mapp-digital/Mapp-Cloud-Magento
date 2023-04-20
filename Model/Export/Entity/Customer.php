<?php

namespace MappDigital\Cloud\Model\Export\Entity;

use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem as MagentoFileSystemManager;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Export\Client\FileSystem as MappFilesystemExport;
use MappDigital\Cloud\Model\Export\Client\Sftp;

class Customer extends ExportAbstract
{
    const ATTRIBUTES_FOR_EXPORT = [
        'email',
        'firstname',
        'lastname',
        'gender',
        'prefix',
        'dob',
    ];

    const ALL_COLUMNS_IN_ORDER = [
        'email',
        'firstname',
        'lastname',
        'gender',
        'prefix',
        'dob'
    ];

    const EXPORT_FILE_PREFIX = 'customer_export_';

    public function __construct(
        protected CustomerCollectionFactory $customerCollectionFactory,
        StoreManagerInterface $storeManager,
        MagentoFileSystemManager $magentoFilesystemManager,
        Sftp $sftp,
        MappFilesystemExport $mappFilesystemExport
    )
    {
        parent::__construct($storeManager, $magentoFilesystemManager, $sftp, $mappFilesystemExport);
    }

    /**
     * @return CustomerCollection
     * @throws LocalizedException
     */
    public function getEntitiesForExport(): CustomerCollection
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToSelect(self::ATTRIBUTES_FOR_EXPORT);
        $customerCollection->addFieldToFilter('website_id', $this->storeManager->getWebsite()->getId() ?? 0);

        return $customerCollection;
    }

    /**
     * @throws LocalizedException
     */
    public function getCsvContentForExport(): array
    {
        $entities = $this->getEntitiesForExport();
        $data = [];

        foreach (static::ALL_COLUMNS_IN_ORDER as $column) {
            $data[] = '"' . $column . '"';
        }

        $rows[] = $data ?? [];

        while ($entity = $entities->fetchItem()) {
            $data = [];
            foreach (static::ALL_COLUMNS_IN_ORDER as $column) {
                $gender = null;
                if ($column == "gender") {
                    $gender = match ($entity->getData($column)) {
                        "1" => "Female",
                        "2" => "Male",
                        default => "Unknown",
                    };
                }

                $data[] = '"' . str_replace(
                        ['"', '\\'],
                        ['""', '\\\\'],
                        $gender ?? $entity->getData($column) ?? ''
                    ) . '"';
            }

            $rows[] = $data;
        }

        return $rows ?? [];
    }
}
