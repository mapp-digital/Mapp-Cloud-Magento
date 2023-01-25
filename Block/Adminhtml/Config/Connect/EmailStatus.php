<?php

namespace MappDigital\Cloud\Block\Adminhtml\Config\Connect;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

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
