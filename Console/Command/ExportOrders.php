<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Model\Export\Entity\Order as OrderExport;

class ExportOrders extends AbstractCommand
{
    const COMMAND_NAME = "mapp:export:orders";

    public function __construct(
        private OrderExport $orderExport,
        ResourceConnection $resource,
        State $state,
        $name = null
    ){
        parent::__construct($resource, $state, $name);
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function doExecute()
    {
        $this->getOutput()->writeln('<info>Starting To Export Orders To CSV...</info>');
        $this->orderExport->execute();
        $this->getOutput()->writeln('<info>Logs Have Been Cleaned</info>');
    }
}
