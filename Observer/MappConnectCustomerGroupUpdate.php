<?php
namespace MappDigital\Cloud\Observer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\Client;
use Psr\Log\LoggerInterface;

class MappConnectCustomerGroupUpdate implements ObserverInterface
{
    protected ScopeConfigInterface $scopeConfig;
    protected ConnectHelper $connectHelper;
    protected LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConnectHelper $connectHelper,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectHelper = $connectHelper;
        $this->logger = $logger;
    }

    /**
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->logger->debug('Mapp Connect: -- EVENT -- customer_save_after_data_object');
        $this->logger->debug('Mapp Connect: -- OBSERVER -- mapp_connect_customer_update_observer');

        if ($this->isEnabled()) {
            try {
                $mappConnectClient = $this->getMappConnectClient();
                $customerData = $observer->getCustomerDataObject()->__toArray();
                $customerData['group'] = $this->connectHelper->getConfigValue('group', 'customers');
                $customerData['subscribe'] = true;

                $this->logger->debug(sprintf('Mapp Connect: -- GROUP -- Sending Customer to group %s', $this->connectHelper->getConfigValue('group', 'customers')), ['data' => $customerData]);
                $mappConnectClient->event('user', $customerData);
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('Mapp Connect: -- ERROR -- Sending Customer to group: %s', $exception->getMessage()), ['exception' => $exception]);
                $this->logger->error($exception);
            }
        }
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    private function isEnabled(): bool
    {
        return (bool) $this->connectHelper->getConfigValue('export', 'customer_enable');
    }

    /**
     * @return Client|null
     * @throws LocalizedException
     */
    private function getMappConnectClient(): ?Client
    {
        return $this->connectHelper->getMappConnectClient();
    }
}
