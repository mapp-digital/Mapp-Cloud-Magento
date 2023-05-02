<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Plugin;

use Closure;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use Psr\Log\LoggerInterface;

class SubscriberPlugin
{
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected ConnectHelper $connectHelper,
        protected CombinedLogger $mappCombinedLogger,
        protected SubscriptionManager $subscriptionManager
    ) {}

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

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $subject->getEmail() ?? $result->getEmail(),
                    true,
                    $subject->getStoreId() ?? $result->getStoreId()
                );

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );
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

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $result->getEmail(),
                    false,
                    $result->getStoreId()
                );

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );
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

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $subject->getEmail(),
                    true,
                    $subject->getStoreId()
                );

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );
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

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $subject->getEmail(),
                    false,
                    $subject->getStoreId()
                );

                $this->mappCombinedLogger->debug(
                    \json_encode(['Type' => 'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client', 'data' => $data]), __CLASS__, __FUNCTION__
                );
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
