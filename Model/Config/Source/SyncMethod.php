<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SyncMethod implements OptionSourceInterface
{
    const SYNC_METHOD_LEGACY = 0;
    const SYNC_METHOD_DB_TRIGGER = 1;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SYNC_METHOD_LEGACY,
                'label' => __('Legacy')
            ],
            [
                'value' => self::SYNC_METHOD_DB_TRIGGER,
                'label' => __('DB Trigger')
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::SYNC_METHOD_LEGACY => __('Legacy'),
            self::SYNC_METHOD_DB_TRIGGER => __('TB Trigger')
        ];
    }
}
