<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block\Adminhtml\Config\Connect;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\ClientFactory as MappConnectClientFactory;
use Magento\Backend\Block\Template\Context;

class ConnectionStatus extends Field
{
    protected ?MappConnectClientFactory $mappConnectClientFactory;
    protected ConnectHelper $connectHelper;

    public function __construct(
        Context $context,
        MappConnectClientFactory $mappConnectClientFactory,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->mappConnectClientFactory = $mappConnectClientFactory;
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * Returns element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $connection = false;
        $mappConnectClient = $this->mappConnectClientFactory->create();

        try {
            if ($mappConnectClient->ping()) {
                $connection = true;
            }
        } catch (Exception|GuzzleException $exception) {}

        if ($connection) {
            $html = "<span style='color: green'>Connection Successful</span>";
        } else {
            $html = "<span style='color: red'>No Connection Made</span>";
        }

        return $html;
    }
}
