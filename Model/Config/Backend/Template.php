<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Config\Backend;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Data\OptionSourceInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use Magento\Framework\Message\ManagerInterface;


class Template implements OptionSourceInterface
{
    private static ?array $cache = null;

    public function __construct(
        protected ConnectHelper $helper,
        protected ManagerInterface $messageManager
    ) {}

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
            if ($this->helper->getMappConnectClient()->ping()) {
                self::$cache = $this->helper->getMappConnectClient()->getMessages() ?? [];
            } else {
                return [];
            }
        } catch (\Exception | \Throwable $e) {
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

        foreach ($this->getMessages() as $value => $label) {
            $default[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $default;
    }
}
