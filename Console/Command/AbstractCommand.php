<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Console\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\State;

/**
 * Class AbstractCommand
 * @package Ayko\ConsoleToolkit\Console\Command
 */
abstract class AbstractCommand extends Command
{
    const COMMAND_NAME = '';
    const COMMAND_DESCRIPTION = '';
    const AREA = 'adminhtml';

    protected ?InputInterface $input;
    protected ?OutputInterface $output;
    protected ResourceConnection $resource;
    protected AdapterInterface $connection;
    protected State $state;

    public function __construct(
        ResourceConnection $resource,
        State $state,
        $name = null
    ) {
        parent::__construct($name);

        $this->resource = $resource;
        $this->connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->state = $state;
    }

    // -----------------------------------------------
    // CONFIGURATION and EXECUTION
    // -----------------------------------------------

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::COMMAND_NAME)
             ->setDescription(static::COMMAND_DESCRIPTION);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $s = time();
        $this->setInput($input)->setOutput($output);

        try {
            // Emulate area for category migration
            $this->state->emulateAreaCode(
                $this::AREA,
                [$this, 'doExecute'],
                []
            );
        } catch (Exception $e) {
            $this->getOutput()->writeln(sprintf(
                "<error>[!] Exception encountered: %s",
                $e->getMessage()
            ));
        }

        if ($this->getVerbosity()) {
            $this->writeRunTime($s);
        }
    }

    /**
     * @return void
     */
    abstract public function doExecute();

    // -----------------------------------------------
    // GETTERS AND SETTERS
    // -----------------------------------------------

    /**
     * @return string
     */
    protected function getAreaCode(): string
    {
        return self::AREA;
    }

    /**
     * @param InputInterface $input
     * @return $this
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @return InputInterface|null
     */
    public function getInput(): ?InputInterface
    {
        return $this->input;
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @return OutputInterface|null
     */
    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getVerbosity(): int
    {
        return $this->getOutput()->getVerbosity();
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

    /**
     * @param $start
     * @return void
     * @throws Exception
     */
    public function writeRunTime($start)
    {
        if (!$this->getOutput()) {
            return;
        }

        $dtF = new \DateTime("@0");
        $dtT = new \DateTime("@".(time()-$start));

        $this->getOutput()->writeln(sprintf(
                '<info>Run time: %s</info>',
                $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds')
        ));

        $this->getOutput()->writeln(sprintf(
                '<info>Peak Memory: %sb</info>',
                number_format(memory_get_peak_usage(true))
        ));
    }
}
