<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Block\Adminhtml\Log\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use MappDigital\Cloud\Api\Data\LogInterface;

class LogLevel extends AbstractRenderer
{
    /**
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        if (isset(LogInterface::ALL_LOG_LEVELS[$row->getSeverity()])) {
            return LogInterface::ALL_LOG_LEVELS[$row->getSeverity()];
        }

        return "Debug";
    }

}
