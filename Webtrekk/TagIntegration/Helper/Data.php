<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    /**
     * @var string
     */
    const XML_PATH_ENABLE = 'tagintegration/general/enable';
    /**
     * @var string
     */
    const XML_PATH_TAGINTEGRATION_ID = 'tagintegration/general/tagintegration_id';
    /**
     * @var string
     */
    const XML_PATH_TAGINTEGRATION_DOMAIN = 'tagintegration/general/tagintegration_domain';
    /**
     * @var string
     */
    const XML_PATH_CUSTOM_DOMAIN = 'tagintegration/general/custom_domain';
    /**
     * @var string
     */
    const XML_PATH_CUSTOM_PATH = 'tagintegration/general/custom_path';
    /**
     * @var string
     */
    const XML_PATH_ATTRIBUTE_BLACKLIST = 'tagintegration/general/attribute_blacklist';
    /**
     * @var string
     */
    const XML_PATH_ADD_TO_CART_EVENT_NAME = 'tagintegration/general/add_to_cart_event_name';

    /**
     * @var Repository
     */
    protected $_assetRepository;

    /**
     * @param Context $context
     * @param Repository $assetRepository
     */
    public function __construct(Context $context, Repository $assetRepository)
    {
        $this->_assetRepository = $assetRepository;

        parent::__construct($context);
    }

    /**
     * @return string|null
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    private function getAttributeBlacklist()
    {
        $attributeBlacklist = $this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTE_BLACKLIST, ScopeInterface::SCOPE_STORE);
        return explode("\r\n", $attributeBlacklist);
    }

    /**
     * @return array
     */
    public function getTagIntegrationConfig()
    {
        return [
            'tiId' => $this->scopeConfig->getValue(self::XML_PATH_TAGINTEGRATION_ID, ScopeInterface::SCOPE_STORE),
            'tiDomain' => $this->scopeConfig->getValue(self::XML_PATH_TAGINTEGRATION_DOMAIN, ScopeInterface::SCOPE_STORE),
            'customDomain' => $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_DOMAIN, ScopeInterface::SCOPE_STORE),
            'customPath' => $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_PATH, ScopeInterface::SCOPE_STORE),
            'option' => (object)[]
        ];
    }

    /**
     * @return string
     */
    public function getAddToCartEventName()
    {
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_ADD_TO_CART_EVENT_NAME, ScopeInterface::SCOPE_STORE);
        return is_null($configValue) ? 'add-to-cart': $configValue;
    }

    public function removeParameterByBlacklist(array $data = [])
    {
        $blacklist = $this->getAttributeBlacklist();
        for ($i = 0, $l = count($blacklist); $i < $l; $i++) {
            $key = $blacklist[$i];

            if (strpos($key, '*') !== false) {
                $keyRegExp = implode('.*', explode('*', $key));
                $matches = preg_grep('/' . $keyRegExp . '/', array_keys($data));
                foreach ($matches as $k => $v) {
                    unset($data[$v]);
                }
            } else {
                if (array_key_exists($key, $data)) {
                    unset($data[$key]);
                }
            }
        }

        $data['blacklist'] = $blacklist;
        return $data;
    }
}
