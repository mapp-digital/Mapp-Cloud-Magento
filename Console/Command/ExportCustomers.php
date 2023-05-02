<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Model\Export\Entity\Customer as CustomerExport;

class ExportCustomers extends AbstractCommand
{
    const COMMAND_NAME = "mapp:export:customers";

    public function __construct(
        private CustomerExport $customerExport,
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
        $this->getOutput()->writeln('<info>Starting To Export Customers To CSV...</info>');
        $this->customerExport->execute();
        $this->getOutput()->writeln('<info>Customers Have Been Exported</info>');
    }
}
