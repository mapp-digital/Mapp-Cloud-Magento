<?php
namespace MappDigital\Cloud\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TrueFalseString implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'true',
                'label' => __('True')
            ],
            [
                'value' => 'false',
                'label' => __('False')
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
            'false' => __('False'),
            'true' => __('True')
        ];
    }
}
