<?php

namespace MappDigital\Cloud\Model\Config\Backend;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Data\OptionSourceInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use Magento\Framework\Message\ManagerInterface;


class Template implements OptionSourceInterface
{
    private static $cache = null;

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
    private function getMessages(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            if ($mappConnectClient = $this->helper->getMappConnectClient()) {
                self::$cache = $mappConnectClient->getMessages();
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
        'label' => __('Magento Default')
        ]];

        foreach ($this->getMessages() as $value => $label) {
            $default[] = ['value' => $value, 'label' => $label];
        }
        return $default;
    }
}
