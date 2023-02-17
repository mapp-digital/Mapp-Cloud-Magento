<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLE = 'tagintegration/general/enable';
    const XML_PATH_TAGINTEGRATION_ID = 'tagintegration/general/tagintegration_id';
    const XML_PATH_TAGINTEGRATION_DOMAIN = 'tagintegration/general/tagintegration_domain';
    const XML_PATH_CUSTOM_DOMAIN = 'tagintegration/general/custom_domain';
    const XML_PATH_CUSTOM_PATH = 'tagintegration/general/custom_path';
    const XML_PATH_ATTRIBUTE_BLACKLIST = 'tagintegration/general/attribute_blacklist';
    const XML_PATH_ADD_TO_CART_EVENT_NAME = 'tagintegration/general/add_to_cart_event_name';
    const XML_PATH_ACQUIRE = 'mapp_acquire/general/acquire';
    const XML_PATH_GTM_ENABLE = 'mapp_gtm/general/gtm_enable';
    const XML_PATH_GTM_LOAD = 'mapp_gtm/general/gtm_load';
    const XML_PATH_GTM_ID = 'mapp_gtm/general/gtm_id';
    const XML_PATH_GTM_DATALAYER = 'mapp_gtm/general/gtm_datalayer';
    const XML_PATH_GTM_TRIGGER_BASKET = 'mapp_gtm/general/gtm_trigger_basket';
    const XML_PATH_GTM_ADD_TO_CART_EVENTNAME = 'mapp_gtm/general/gtm_add_to_cart_eventname';

    protected Repository $assetRepository;

    public function __construct(
        Context $context,
        Repository $assetRepository
    ) {
        $this->assetRepository = $assetRepository;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE, ScopeInterface::SCOPE_STORE) || $this->scopeConfig->isSetFlag(self::XML_PATH_GTM_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    private function getAttributeBlacklist()
    {
        $attributeBlacklist = $this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTE_BLACKLIST, ScopeInterface::SCOPE_STORE);
        return preg_split("/(?:\r\n|,)/", $attributeBlacklist);
    }

    /**
     * @return string | null
     */
    private function getAcquireLink()
    {
        $acquireScript = $this->scopeConfig->getValue(self::XML_PATH_ACQUIRE, ScopeInterface::SCOPE_STORE);

        if(!is_null($acquireScript) && preg_match('/id=(\d+?)&m=(\d+?)\D/', $acquireScript, $ids)) {
            return 'https://c.flx1.com/' . $ids[2] . '-' . $ids[1] .'.js?id=' . $ids[1] . '&m=' . $ids[2];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getGTMConfig(): array
    {
        return [
            'enable' => $this->scopeConfig->getValue(self::XML_PATH_GTM_ENABLE, ScopeInterface::SCOPE_STORE),
            'load' => $this->scopeConfig->getValue(self::XML_PATH_GTM_LOAD, ScopeInterface::SCOPE_STORE),
            'id' => $this->scopeConfig->getValue(self::XML_PATH_GTM_ID, ScopeInterface::SCOPE_STORE),
            'datalayer' => $this->scopeConfig->getValue(self::XML_PATH_GTM_DATALAYER, ScopeInterface::SCOPE_STORE),
            'triggerBasket' => $this->scopeConfig->getValue(self::XML_PATH_GTM_TRIGGER_BASKET, ScopeInterface::SCOPE_STORE),
            'event' => $this->scopeConfig->getValue(self::XML_PATH_GTM_ADD_TO_CART_EVENTNAME, ScopeInterface::SCOPE_STORE)
        ];
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'tiEnable' => $this->scopeConfig->getValue(self::XML_PATH_ENABLE, ScopeInterface::SCOPE_STORE),
            'tiId' => $this->scopeConfig->getValue(self::XML_PATH_TAGINTEGRATION_ID, ScopeInterface::SCOPE_STORE),
            'tiDomain' => $this->scopeConfig->getValue(self::XML_PATH_TAGINTEGRATION_DOMAIN, ScopeInterface::SCOPE_STORE),
            'customDomain' => $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_DOMAIN, ScopeInterface::SCOPE_STORE),
            'customPath' => $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_PATH, ScopeInterface::SCOPE_STORE),
            'option' => (object)[],
            'acquire' => $this->getAcquireLink(),
            'gtm' => $this->getGTMConfig()
        ];
    }

    /**
     * @return string
     */
    public function getAddToCartEventName(): string
    {
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_ADD_TO_CART_EVENT_NAME, ScopeInterface::SCOPE_STORE);
        return is_null($configValue) ? 'add-to-cart': $configValue;
    }

    /**
     * @param array $data
     * @return array
     */
    public function removeParameterByBlacklist(array $data = []): array
    {
        $blacklist = $this->getAttributeBlacklist();
        for ($i = 0, $l = count($blacklist); $i < $l; $i++) {
            $key = $blacklist[$i];

            if (str_contains($key, '*')) {
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
