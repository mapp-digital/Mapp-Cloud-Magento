<?php

namespace MappDigital\Cloud\Model\Config\Backend;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Data\OptionSourceInterface;
use MappDigital\Cloud\Helper\Data;
use Magento\Framework\Message\ManagerInterface;

class Group implements OptionSourceInterface
{
    private static array $cache = [];

    protected Data $helper;
    protected ManagerInterface $messageManager;

    public function __construct(
        Data $helper,
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
            if ($mappConnectClient = $this->helper->getMappConnectClient()) {
                self::$cache = $mappConnectClient->getGroups();
            } else {
                return [];
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
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
        $default = [[
        'value' => 0,
        'label' => __('Integration Default')
        ]];

        foreach ($this->getGroups() as $value => $label) {
            $default[] = ['value' => $value, 'label' => $label];
        }

        return $default;
    }
}
