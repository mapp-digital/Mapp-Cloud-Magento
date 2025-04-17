<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Block;

use Magento\Framework\View\Element\Template;

/**
 * @method array getConfig
 * @method int|null getProductId
 * @method string getStoreCode
 */
class TrackingScript extends Template
{
    public const PS_VERSION = "1.2.6";

    /**
     * @return bool
     */
    public function getGtmEnabled(): bool
    {
        return $this->getConfig()['gtm']['enable'] ?? false;
    }

    /**
     * @return string
     */
    public function getGtmLoad(): string
    {
        return $this->getConfig()['gtm']['load'] ?? '';
    }

    /**
     * @return string
     */
    public function getAquire(): string
    {
        return $this->getConfig()['acquire'] ?? '';
    }

    /**
     * @return bool
     */
    public function getTiEnable(): bool
    {
        return $this->getConfig()['tiEnable'] ?? false;
    }
}
