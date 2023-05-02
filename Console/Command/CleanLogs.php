<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use MappDigital\Cloud\Cron\Log\Clean;

class CleanLogs extends AbstractCommand
{
    const COMMAND_NAME = "mapp:logs:clean";

    public function __construct(
        private Clean $cleanLogsCron,
        ResourceConnection $resource,
        State $state,
        $name = null
    ){
        parent::__construct($resource, $state, $name);
    }

    /**
     * Access Cron Clean class in order to trigger the log cleaning manually if required
     *
     * @return void
     */
    public function doExecute()
    {
        $this->getOutput()->writeln('<info>Starting To Clean Logs...</info>');
        $this->cleanLogsCron->execute();
        $this->getOutput()->writeln('<info>Logs Have Been Cleaned</info>');
    }
}
