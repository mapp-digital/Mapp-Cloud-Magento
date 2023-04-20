<?php
namespace MappDigital\Cloud\Model\Export\Entity;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem as MagentoFileSystemManager;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriter;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Export\Client\FileSystem as MappFilesystemExport;
use MappDigital\Cloud\Model\Export\Client\Sftp;

abstract class ExportAbstract
{
    const ATTRIBUTES_FOR_EXPORT = [];
    const EXPORT_FILE_PREFIX = '';

    protected DirectoryWriter $directoryWriter;

    public function __construct(
        protected StoreManagerInterface $storeManager,
        protected MagentoFileSystemManager $magentoFileSystemManager,
        protected Sftp $sftp,
        protected MappFilesystemExport $mappFilesystemExport
    )
    {
        $this->directoryWriter = $this->magentoFileSystemManager->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws FileSystemException
     */
    public function execute()
    {
        $filename = static::EXPORT_FILE_PREFIX . substr(hash('sha256', microtime()), 0, 5) . '.csv';
        $dirPath = rtrim($this->magentoFileSystemManager->getUri(DirectoryList::VAR_DIR) . $this->mappFilesystemExport->getLocalSystemFilepathForGeneratedFile(), '/') . '/';
        $filePath = $dirPath . $filename;

        $this->directoryWriter->create($dirPath);

        $stream = $this->directoryWriter->openFile($filePath, 'w+');

        foreach (array_chunk($this->getCsvContentForExport(),1000) as $rows) {
            foreach ($rows as $row) {
                $stream->writeCsv($row);
            }
        }

        $stream->close();

        if ($this->sftp->isSftpExportEnabled()) {
            $connection = $this->sftp->createConnectionAndGoToConfiguredFilepath();
            $connection->write($filename, $stream->readAll());
            $connection->close();
        }
    }

    abstract public function getEntitiesForExport();

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
}
