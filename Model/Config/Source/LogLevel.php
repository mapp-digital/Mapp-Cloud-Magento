<?php

namespace MappDigital\Cloud\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MappDigital\Cloud\Api\Data\LogInterface;

class LogLevel implements OptionSourceInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = [
            'label' => 'None',
            'value' => 0
        ];

        foreach (LogInterface::ALL_LOG_LEVELS as $level => $label) {
            $options[] = [
                'label' => $label,
                'value' => $level
            ];
        }

        return $options ?? [];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options[0] = 'None';

        foreach (LogInterface::ALL_LOG_LEVELS as $level => $label) {
            $options[$level] = [$label];
        }

        return $options ?? [];
    }
}
