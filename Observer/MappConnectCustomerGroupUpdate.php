<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\Client;

class MappConnectCustomerGroupUpdate implements ObserverInterface
{
    protected ScopeConfigInterface $scopeConfig;
    protected ConnectHelper $connectHelper;
    protected CombinedLogger $mappCombinedLogger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConnectHelper $connectHelper,
        CombinedLogger $mappCombinedLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectHelper = $connectHelper;
        $this->mappCombinedLogger = $mappCombinedLogger;
    }

    /**
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->mappCombinedLogger->debug('Mapp Connect: -- EVENT -- customer_save_after_data_object, Mapp Connect: -- OBSERVER -- mapp_connect_customer_update_observer ', __CLASS__, __FUNCTION__);

        if ($this->isEnabled()) {
            try {
                $mappConnectClient = $this->getMappConnectClient();
                $customerData = $observer->getCustomerDataObject()->__toArray();
                $customerData['group'] = $this->connectHelper->getConfigValue('group', 'customers');
                $customerData['subscribe'] = true;

                $this->mappCombinedLogger->debug(
                    sprintf('Mapp Connect: -- GROUP -- Sending Customer data to group %s', $this->connectHelper->getConfigValue('group', 'customers')),
                    __CLASS__, __FUNCTION__,
                    ['data' => $customerData]
                );

                $this->mappCombinedLogger->info(
                    \json_encode($customerData, JSON_PRETTY_PRINT),
                    __CLASS__, __FUNCTION__,
                    ['data' => $customerData]
                );

                $mappConnectClient->event('user', $customerData);
            } catch (\Exception $exception) {
                $this->mappCombinedLogger->error(sprintf('Mapp Connect: -- ERROR -- Sending Customer to group: %s', $exception->getMessage()), __CLASS__, __FUNCTION__, ['exception' => $exception]);
                $this->mappCombinedLogger->error($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
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
