<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block\Adminhtml\Config\WebPush;


use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class WebPushStatus extends Field
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
        return "<span>This functionality is currently <span style='color: red'>Disabled.</span> Please ensure you enable Mapp Web Push via the General tab";;
    }
}
