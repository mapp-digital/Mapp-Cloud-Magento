<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SubscribeEmailTestTool extends AbstractCommand
{
    const COMMAND_NAME = "mapp:newsletter:subscription-email";
    const AREA = 'frontend';

    public function __construct(
        private SubscriberFactory $subscriberFactory,
        private StoreManager $storeManager,
        ResourceConnection $resource,
        State $state,
        $name = null
    ){
        parent::__construct($resource, $state, $name);
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument(
            'email',
            InputArgument::REQUIRED,
            'Customer ID to Subscribe or Unsubscribe'
        );

        $this->addArgument(
            'store_id',
            InputArgument::OPTIONAL,
            'Store ID used for unsubscription. No argument in subscribe method to be able to use it'
        );

        $this->addOption(
            'unsubscribe',
            InputOption::VALUE_NONE
        );

        parent::configure();
    }

    /**
     * Access (Un)Subscribe by customer ID functions via console command due to them being deprecated within Magento Propper
     *
     * @return void
     * @throws LocalizedException
     */
    public function doExecute()
    {
        $this->getOutput()->writeln('<info>Updating Subscription by Email...</info>');

        if (!$this->getInput()->getOption('unsubscribe')) {
            $this->subscriberFactory->create()->subscribe(
                $this->getInput()->getArgument('email')
            );
            return;
        }

        $this->subscriberFactory->create()
            ->loadBySubscriberEmail(
                $this->getInput()->getArgument('email'),
                $this->storeManager->getStore($this->getInput()->getArgument('store_id'))->getWebsiteId()
            )
            ->unsubscribe();

        $this->getOutput()->writeln('<info>Email Subscription Updated</info>');
    }
}
