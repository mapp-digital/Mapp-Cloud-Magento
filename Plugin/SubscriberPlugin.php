<?php
namespace MappDigital\Cloud\Plugin;

use Closure;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use Psr\Log\LoggerInterface;

class SubscriberPlugin
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

    // -----------------------------------------------
    // LEGACY METHODS VIA PLUGIN INTERCEPTORS
    // -----------------------------------------------

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSubscribe($subject, $result)
    {
        if (!$result || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->info('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Subscribe', __CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribe', __CLASS__, __FUNCTION__);

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
                ];

                if ($this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        } catch (Exception $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterUnsubscribe($subject, $result)
    {
        if (!$result->isStatusChanged() || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->info('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Unsubscribe', __CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribe', __CLASS__, __FUNCTION__);

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers'),
                    'unsubscribe' => true
                ];

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        } catch (Exception $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        }


        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSubscribeCustomerById($subject, $result)
    {
        if (!$result->isStatusChanged() || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->info('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Subscribe Existing Customer', __CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribeCustomerById', __CLASS__, __FUNCTION__);

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
                ];

                if ($this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        } catch (Exception $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        }

        return $result;
    }

    /**
     * @param $subject
     * @param Closure $proceed
     * @param $customerId
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundUnsubscribeCustomerById($subject, Closure $proceed, $customerId)
    {
        $result = $proceed($customerId);

        if (!$subject->isStatusChanged() || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->info('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Unsubscribe Existing Customer', __CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribeCustomerById', __CLASS__, __FUNCTION__);


                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers'),
                    'unsubscribe' => true
                ];

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        } catch (Exception $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(),__CLASS__, __FUNCTION__);
        }

        return $result;
    }
}
