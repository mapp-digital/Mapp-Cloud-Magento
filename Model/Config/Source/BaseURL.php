<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class BaseURL implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'https://jamie.g.shortest-route.com/charon/api/v1/',
                'label' => 'EU L3 cluster (https://jamie.g.shortest-route.com/charon/api/v1/)'],
            ['value' => 'https://jamie.a.shortest-route.com/charon/api/v1/',
                'label' => 'US L3 cluster (https://jamie.a.shortest-route.com/charon/api/v1/)'],
            ['value' => 'https://jamie.h.shortest-route.com/charon/api/v1/',
                'label' => 'EMC cluster (https://jamie.h.shortest-route.com/charon/api/v1/)'],
            ['value' => 'https://jamie.c.shortest-route.com/charon/api/v1/',
                'label' => 'EMC-US cluster (https://jamie.c.shortest-route.com/charon/api/v1/)'],
            ['value' => 'custom', 'label' => __('Specified')]
        ];
    }
}
