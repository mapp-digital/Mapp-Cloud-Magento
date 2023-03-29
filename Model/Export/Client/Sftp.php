<?php

namespace MappDigital\Cloud\Model\Export\Client;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\Sftp as MagentoSftpConnector;
use Magento\Framework\Validation\ValidationException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Model\Config\Source\ExportMethod;

class Sftp
{
    const KEY_HOSTNAME = 'hostname';
    const KEY_PORT = 'port';
    const KEY_USERNAME = 'username';
    const KEY_PASSWORD = 'password';
    const KEY_FILEPATH = 'filepath';

    const XML_PATH_EXPORT_METHOD = 'mapp_exports/general/export_method';
    const XML_PATH_SFTP_HOSTNAME = 'mapp_exports/general/hostname';
    const XML_PATH_SFTP_PORT = 'mapp_exports/general/port';
    const XML_PATH_SFTP_USERNAME = 'mapp_exports/general/username';
    const XML_PATH_SFTP_PASSWORD = 'mapp_exports/general/password';
    const XML_PATH_SFTP_FILEPATH = 'mapp_exports/general/filepath';

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
     * Connect to an SFTP server using specified configuration
     *
     * @return MagentoSftpConnector
     * @throws LocalizedException
     * @throws ValidationException
     */
    public function createConnectionAndGoToConfiguredFilepath(): MagentoSftpConnector
    {
        $configuration = $this->getSftpConfiguration();
        $this->validateSftpConfigurationData($configuration);

        $connection = new MagentoSftpConnector();
        $connection->open(
            ['host' => $configuration[self::KEY_HOSTNAME] . ':' . $configuration[self::KEY_PORT], 'username' => $configuration[self::KEY_USERNAME], 'password' => $configuration[self::KEY_PASSWORD]]
        );

        $connection->cd($configuration[self::KEY_FILEPATH]);
        return $connection;
    }


    /**
     * @return array
     * @throws LocalizedException
     */
    public function getSftpConfiguration(): array
    {
        return [
            self::KEY_HOSTNAME  => $this->scopeConfig->getValue(self::XML_PATH_SFTP_HOSTNAME, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite()),
            self::KEY_PORT      => $this->scopeConfig->getValue(self::XML_PATH_SFTP_PORT, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite()),
            self::KEY_USERNAME  => $this->scopeConfig->getValue(self::XML_PATH_SFTP_USERNAME, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite()),
            self::KEY_PASSWORD  => $this->scopeConfig->getValue(self::XML_PATH_SFTP_PASSWORD, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite()),
            self::KEY_FILEPATH  => $this->scopeConfig->getValue(self::XML_PATH_SFTP_FILEPATH, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite()),
        ];
    }

    /**
     * @param array $configurationData
     * @return void
     * @throws ValidationException
     */
    private function validateSftpConfigurationData(array $configurationData)
    {
        foreach ([self::KEY_HOSTNAME, self::KEY_PORT, self::KEY_USERNAME, self::KEY_PASSWORD, self::KEY_FILEPATH] as $requiredKey) {
            if (!array_key_exists($requiredKey, $configurationData)) {
                throw new ValidationException(__("MAPP SFTP Configuration is Partially or Completely Missing."));
            }

            if (strlen((string)$configurationData[$requiredKey]) === 0) {
                throw new ValidationException(__("MAPP SFTP Configuration is Partially or Completely Incomplete."));
            }
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isSftpExportEnabled()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_EXPORT_METHOD, ScopeInterface::SCOPE_WEBSITE, $this->storeManager->getWebsite())
            == ExportMethod::EXPORT_METHOD_SFTP;
    }
}
