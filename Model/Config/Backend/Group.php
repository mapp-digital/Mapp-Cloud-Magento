<?php

namespace MappDigital\Cloud\Model\Config\Backend;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Data\OptionSourceInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use Magento\Framework\Message\ManagerInterface;

class Group implements OptionSourceInterface
{
    private static ?array $cache = null;

    protected ConnectHelper $helper;
    protected ManagerInterface $messageManager;

    public function __construct(
        ConnectHelper $helper,
        ManagerInterface $messageManager
    ) {
        $this->helper = $helper;
        $this->messageManager = $messageManager;
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    private function getGroups(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            if ($this->helper->getMappConnectClient()->ping()) {
                self::$cache = $this->helper->getMappConnectClient()->getGroups() ?? [];
            } else {
                return [];
            }
        } catch (\Exception $e) {
            self::$cache = [];
        }

        return self::$cache;
    }

    /**
     * @return array[]
     * @throws GuzzleException
     */
    public function toOptionArray()
    {
        $default = [
            [
                'value' => 0,
                'label' => __('Magento Default')
            ]
        ];

        foreach ($this->getGroups() as $value => $label) {
            $default[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $default;
    }
}
