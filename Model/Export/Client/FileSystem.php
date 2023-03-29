<?php

namespace MappDigital\Cloud\Model\Export\Client;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\Sftp as MagentoSftpConnector;
use Magento\Framework\Validation\ValidationException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Config\Source\ExportMethod;

class FileSystem
{
    const XML_PATH_LOCAL_FILEPATH = 'mapp_exports/general/local_filepath';

    protected StoreManagerInterface $storeManager;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getLocalSystemFilepathForGeneratedFile(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_LOCAL_FILEPATH, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite()) ?? '';
    }
}
