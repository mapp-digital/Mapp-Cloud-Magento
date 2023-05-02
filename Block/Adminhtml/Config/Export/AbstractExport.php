<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block\Adminhtml\Config\Export;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class AbstractExport extends Field
{
    const URL_ROUTE = '';
    const LINK_MESSAGE = 'Queue Export Now';

    /**
     * Returns element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return '<input type="button" onclick="location.href=\'' . $this->getUrl(static::URL_ROUTE) .  '\';" value="'. static::LINK_MESSAGE .'" />';
    }
}
