<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ExportMethod implements OptionSourceInterface
{
    const EXPORT_METHOD_SFTP = 0;
    const EXPORT_METHOD_FILESYSTEM = 1;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::EXPORT_METHOD_SFTP,
                'label' => __('SFTP')
            ],
            [
                'value' => self::EXPORT_METHOD_FILESYSTEM,
                'label' => __('Local Filesystem Only')
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
            self::EXPORT_METHOD_SFTP => __('SFTP'),
            self::EXPORT_METHOD_FILESYSTEM => __('Local Filesystem Only')
        ];
    }
}
