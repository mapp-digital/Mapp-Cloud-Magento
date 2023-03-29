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
use MappDigital\Cloud\Model\Export\Entity\Customer as CustomerExport;

class ExportCustomers extends AbstractCommand
{
    const COMMAND_NAME = "mapp:export:customers";

    private CustomerExport $customerExport;

    public function __construct(
        ResourceConnection $resource,
        State $state,
        CustomerExport $customerExport,
        $name = null
    ){
        parent::__construct($resource, $state, $name);
        $this->customerExport = $customerExport;
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
        $this->getOutput()->writeln('<info>Logs Have Been Cleaned</info>');
    }
}
