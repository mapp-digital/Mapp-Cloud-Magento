<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use MappDigital\Cloud\Enum\GTM\ConfigurationPaths as GTMConfigPaths;
use MappDigital\Cloud\Enum\TagIntegration\ConfigurationPaths as TagIntegrationConfigPaths;

class Config extends AbstractHelper
{
    public function __construct(
        protected Repository $assetRepository,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(TagIntegrationConfigPaths::XML_PATH_ENABLE->value, ScopeInterface::SCOPE_STORE) || $this->scopeConfig->isSetFlag(GTMConfigPaths::XML_PATH_GTM_ENABLE->value, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return array
     */
    private function getAttributeBlacklist(): array
    {
        $attributeBlacklist = $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_ATTRIBUTE_BLACKLIST->value, ScopeInterface::SCOPE_STORE);
        return preg_split("/(?:\r\n|,)/", $attributeBlacklist ?? '') ?: [];
    }

    /**
     * @return array
     */
    public function getGTMConfig(): array
    {
        return [
            'enable' => $this->scopeConfig->getValue(GTMConfigPaths::XML_PATH_GTM_ENABLE->value, ScopeInterface::SCOPE_STORE),
            'load' => $this->scopeConfig->getValue(GTMConfigPaths::XML_PATH_GTM_LOAD->value, ScopeInterface::SCOPE_STORE),
            'id' => $this->scopeConfig->getValue(GTMConfigPaths::XML_PATH_GTM_ID->value, ScopeInterface::SCOPE_STORE),
            'datalayer' => $this->scopeConfig->getValue(GTMConfigPaths::XML_PATH_GTM_DATALAYER->value, ScopeInterface::SCOPE_STORE),
            'triggerBasket' => $this->scopeConfig->getValue(GTMConfigPaths::XML_PATH_GTM_TRIGGER_BASKET->value, ScopeInterface::SCOPE_STORE),
            'event' => $this->scopeConfig->getValue(GTMConfigPaths::XML_PATH_GTM_ADD_TO_CART_EVENTNAME->value, ScopeInterface::SCOPE_STORE)
        ];
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'tiEnable' => $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_ENABLE->value, ScopeInterface::SCOPE_STORE),
            'tiId' => $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_TAGINTEGRATION_ID->value, ScopeInterface::SCOPE_STORE),
            'tiDomain' => $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_TAGINTEGRATION_DOMAIN->value, ScopeInterface::SCOPE_STORE),
            'customDomain' => $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_CUSTOM_DOMAIN->value, ScopeInterface::SCOPE_STORE),
            'customPath' => $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_CUSTOM_PATH->value, ScopeInterface::SCOPE_STORE),
            'option' => (object)[],
            'gtm' => $this->getGTMConfig()
        ];
    }

    /**
     * @return string
     */
    public function getAddToCartEventName(): string
    {
        return $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_ADD_TO_CART_EVENT_NAME->value, ScopeInterface::SCOPE_STORE) ?? 'add-to-cart';
    }

    /**
     * @return string
     */
    public function getRemoveFromCartEventName(): string
    {
        return $this->scopeConfig->getValue(TagIntegrationConfigPaths::XML_PATH_REMOVE_FROM_CART_EVENT_NAME->value, ScopeInterface::SCOPE_STORE) ?? 'remove-from-cart';
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
