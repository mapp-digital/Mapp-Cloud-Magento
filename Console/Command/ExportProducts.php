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
use MappDigital\Cloud\Model\Export\Entity\Product as ProductExport;

class ExportProducts extends AbstractCommand
{
    const COMMAND_NAME = "mapp:export:products";

    private ProductExport $productExport;

    public function __construct(
        ResourceConnection $resource,
        State $state,
        ProductExport $productExport,
        $name = null
    ){
        parent::__construct($resource, $state, $name);
        $this->productExport = $productExport;
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function doExecute()
    {
        $this->getOutput()->writeln('<info>Starting To Export Orders To CSV...</info>');
        $this->productExport->execute();
        $this->getOutput()->writeln('<info>Logs Have Been Cleaned</info>');
    }
}
