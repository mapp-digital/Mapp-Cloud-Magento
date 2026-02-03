<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OrderItemExportMode implements OptionSourceInterface
{
    const DEFAULT = 'default';
    const PARENT_ONLY = 'parent_only';
    const CHILD_ONLY = 'child_only';
    const PARENT_AND_CHILD = 'parent_and_child';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::DEFAULT, 'label' => __('Default')],
            ['value' => self::PARENT_ONLY, 'label' => __('Parent Only')],
            ['value' => self::CHILD_ONLY, 'label' => __('Child Only')],
            ['value' => self::PARENT_AND_CHILD, 'label' => __('Parent and Child')]
        ];
    }
}
