<?php

namespace MappDigital\Cloud\Model\Config\Backend;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use MappDigital\Cloud\Model\Connect\ClientFactory as MappConnectClientFactory;
use MappDigital\Cloud\Helper\Data as MappConnectHelper;

class Authentication extends Value
{
    const CONFIG_PREFIX = 'mapp_connect';

    protected string $magentoUrlForRequest = '';

    protected ConfigInterface $configResource;
    protected ?MappConnectClientFactory $mappConnectClientFactory;
    protected ProductMetadataInterface $productMetadata;
    protected MappConnectHelper $mappConnectHelper;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ConfigInterface $configResource,
        MappConnectClientFactory $mappConnectClientFactory,
        ProductMetadataInterface $productMetadata,
        MappConnectHelper $mappConnectHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configResource = $configResource;
        $this->productMetadata = $productMetadata;
        $this->mappConnectHelper = $mappConnectHelper;
        $this->mappConnectClientFactory = $mappConnectClientFactory;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        if ($this->mappConnectHelper->getConfigValue('integration', 'integration_enable')) {
            $mappConnectClient = $this->mappConnectClientFactory->create();

            try {
                if (!$mappConnectClient->ping()) {
                    throw new ValidatorException(__('Mapp Connection could not be established.'));
                }

                $response = $mappConnectClient->connect([
                    'params' => [
                        'magentourl' => $this->getMagentoUrlForRequest(),
                        'magentoversion' => $this->productMetadata->getVersion(),
                        'magentoname' => ($this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT) .
                            ($this->getScopeCode() ? "|" . $this->getScopeCode() : ""),
                        'website' => $this->getMagentoUrlForRequest()
                    ]
                ]);

                if ($response['customersGroupId'] !== null) {
                    $this->configResource->saveConfig(
                        self::CONFIG_PREFIX . '/group/customers',
                        $response['customersGroupId'],
                        $this->getScope(),
                        $this->getScopeId()
                    );
                }

                if ($response['subscribersGroupId'] !== null) {
                    $this->configResource->saveConfig(
                        self::CONFIG_PREFIX . '/group/subscribers',
                        $response['subscribersGroupId'],
                        $this->getScope(),
                        $this->getScopeId()
                    );
                }

                if ($response['guestsGroupId'] !== null) {
                    $this->configResource->saveConfig(
                        self::CONFIG_PREFIX . '/group/guests',
                        $response['guestsGroupId'],
                        $this->getScope(),
                        $this->getScopeId()
                    );
                }

            } catch (ServerException|GuzzleException $exception) {
                $this->_logger->critical($exception->getResponse()->getBody() ?? $exception);

                if ($data = json_decode($exception->getResponse()->getBody(), true)) {
                    throw new ValidatorException(__($data['message']));
                }

                throw new ValidatorException(__($exception->getResponse()->getBody()));
            }
        }

        parent::beforeSave();
    }

    /**
     * @return string
     */
    private function getMagentoUrlForRequest(): string
    {
        if (is_null($this->magentoUrlForRequest)) {
            $baseUrl = (string)$this->_config->getValue(
                'web/secure/base_url',
                $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->getScopeCode()
            );

            $baseUrl = preg_replace("(^https?://)", "", $baseUrl);
            $this->magentoUrlForRequest = preg_replace("/[^A-Za-z0-9._]/", "", $baseUrl);
        }

        return $this->magentoUrlForRequest;
    }
}
