<?php

namespace MappDigital\Cloud\Model\Config\Backend;

use \MappDigital\Cloud\Helper\Data;
use \Magento\Framework\Message\ManagerInterface;

class Template implements \Magento\Framework\Option\ArrayInterface
{

    protected $_helper;
    protected $_messageManager;

    public function __construct(
        Data $helper,
        ManagerInterface $messageManager
    ) {
        $this->_helper = $helper;
        $this->_messageManager = $messageManager;
    }

    private static $cache = null;

    private function getMessages()
    {
        if (self::$cache != null) {
            return self::$cache;
        }
        try {
            if ($mc = $this->_helper->getMappConnectClient()) {
                self::$cache = $mc->getMessages();
            } else {
                return [];
            }
        } catch (\Exception $e) {
            $this->_messageManager->addExceptionMessage($e);
            self::$cache = [];
        }
        return self::$cache;
    }

    public function toOptionArray()
    {
        $ret = [[
        'value' => 0,
        'label' => __('Magento Default')
        ]];
        foreach ($this->getMessages() as $value => $label) {
            array_push($ret, ['value' => $value, 'label' => $label]);
        }
        return $ret;
    }
}
