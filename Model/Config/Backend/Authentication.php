<?php

namespace MappDigital\Cloud\Model\Config\Backend;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\App\ObjectManager;

class Authentication extends \Magento\Framework\App\Config\Value
{

    const CONFIG_PREFIX = 'mappconnect';

    protected $_configResource;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ConfigInterface $configResource,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_configResource = $configResource;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function beforeSave()
    {

        if ($this->getConfigValue('integration', 'integration_enable')) {
            $url = $this->getConfigValue('general', 'base_url');
            if ($url == 'custom') {
                $url = $this->getConfigValue('general', 'base_url_custom');
            }

            $mc = new \MappDigital\Cloud\Client(
                $url,
                $this->getConfigValue('integration', 'integration_id'),
                $this->getConfigValue('integration', 'integration_secret')
            );

            try {
              if (!$mc->ping()) {
                throw new ValidatorException(__('Authentication failed.'));
              }
            } catch(\GuzzleHttp\Exception\ServerException $e) {
              if ($data = json_decode($e->getResponse()->getBody(), true)) {
                throw new ValidatorException(__($data['message']));
              }
              throw new ValidatorException(__($e->getResponse()->getBody()));
            }

            $objectManager = ObjectManager::getInstance();
            $magentourl = (string)$this->_config->getValue(
                'web/secure/base_url',
                $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->getScopeCode()
            );
            $magentourl = preg_replace("(^https?://)", "", $magentourl );
            $magentourl = preg_replace("/[^A-Za-z0-9._]/", "", $magentourl );
            $version = $objectManager->get(\Magento\Framework\App\ProductMetadataInterface::class)->getVersion();
            try {
              $resp = $mc->connect([
                'params' => [
                  'magentourl' => $magentourl,
                  'magentoversion' => $version,
                  'magentoname' => ($this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT) .
                    ($this->getScopeCode() ? "|" .$this->getScopeCode() : ""),
                  'website' => $magentourl
                ]
              ]);
            } catch(\GuzzleHttp\Exception\ServerException $e) {
              if ($data = json_decode($e->getResponse()->getBody(), true)) {
                throw new ValidatorException(__($data['message']));
              }
              throw new ValidatorException(__($e->getResponse()->getBody()));
            }

            if ($resp['customersGroupId'] !== null) {
                $this->_configResource->saveConfig(
                    self::CONFIG_PREFIX . '/group/customers',
                    $resp['customersGroupId'],
                    $this->getScope(),
                    $this->getScopeId()
                );
            }
            if ($resp['subscribersGroupId'] !== null) {
                $this->_configResource->saveConfig(
                    self::CONFIG_PREFIX . '/group/subscribers',
                    $resp['subscribersGroupId'],
                    $this->getScope(),
                    $this->getScopeId()
                );
            }
            if ($resp['guestsGroupId'] !== null) {
                $this->_configResource->saveConfig(
                    self::CONFIG_PREFIX . '/group/guests',
                    $resp['guestsGroupId'],
                    $this->getScope(),
                    $this->getScopeId()
                );
            }

        }
        parent::beforeSave();
    }

    private function getConfigValue(string $group, string $field)
    {
        if ($this->getData("groups/$group/fields/$field/value")) {
            return (string)$this->getData("groups/$group/fields/$field/value");
        }

        return (string)$this->_config->getValue(
            self::CONFIG_PREFIX . "/" . $group . "/" . $field,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );
    }
}
