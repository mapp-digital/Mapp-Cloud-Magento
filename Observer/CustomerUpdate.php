<?php
namespace MappDigital\Cloud\Observer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\Data;
use MappDigital\Cloud\Model\Connect\Client;
use Psr\Log\LoggerInterface;

class CustomerUpdate implements ObserverInterface
{
    protected ScopeConfigInterface $scopeConfig;
    protected Data $helper;
    protected LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
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

        if (($mappConnectClient = $this->getMappConnectClient()) && $this->isEnabled()) {
            $customerData = $observer->getCustomerDataObject()->__toArray();
            $customerData['group'] = $this->helper->getConfigValue('group', 'customers');
            $customerData['subscribe'] = true;

            $this->logger->debug('Mapp Connect: Updating Customer', ['data' => $customerData]);
            try {
                $mappConnectClient->event('user', $customerData);
            } catch (\Exception $exception) {
                $this->logger->error('Mapp Connect: cannot sync customer', ['exception' => $exception]);
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
        return (bool) $this->helper->getConfigValue('export', 'customer_enable');
    }

    /**
     * @return Client|null
     * @throws LocalizedException
     */
    private function getMappConnectClient(): ?Client
    {
        return $this->helper->getMappConnectClient();
    }
}
