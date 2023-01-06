<?php

namespace MappDigital\Cloud\Block\Adminhtml\Config\Connect;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\ClientFactory as MappConnectClientFactory;
use Magento\Backend\Block\Template\Context;

class EmailStatus extends Field
{
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
        return "<span>This functionality is currently <span style='color: red'>Disabled.</span> Please ensure you enable Mapp emails via the General tab";;
    }
}
