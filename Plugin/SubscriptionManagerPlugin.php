<?php
namespace MappDigital\Cloud\Plugin;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\Client;
use Psr\Log\LoggerInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;

class SubscriptionManagerPlugin
{
    protected ScopeConfigInterface $scopeConfig;
    protected CustomerRepositoryInterface $customerRepository;
    protected ConnectHelper $connectHelper;
    protected CombinedLogger $mappCombinedLogger;
    protected SubscriptionManager $subscriptionManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        ConnectHelper $connectHelper,
        SubscriptionManager $subscriptionManager,
        CombinedLogger $mappCombinedLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository= $customerRepository;
        $this->connectHelper = $connectHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->mappCombinedLogger = $mappCombinedLogger;
    }

    /**
     * @param $subject
     * @param $result
     * @param $email
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSubscribe($subject, $result, $email)
    {
        if (!$result || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->info('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Subscribe', __CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribe', __CLASS__, __FUNCTION__);

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $email,
                    true
                );

                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- Sent Event Via LEGACY Connect Client', __CLASS__, __FUNCTION__);
            }
        } catch (\Exception | GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Failure to send guest newsletter via connect API', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
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
        if (!$result || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->info('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Unsubscribe',__CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribe',__CLASS__, __FUNCTION__);

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $subject->getEmail(),
                    false
                );

                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- Sent Event Via LEGACY Connect Client', __CLASS__, __FUNCTION__);
            }
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        } catch (LocalizedException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred',__CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @param $customerId
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSubscribeCustomer($subject, $result, $customerId)
    {
        if (!$result || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->mappCombinedLogger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Subscribe Existing Customer', __CLASS__, __FUNCTION__);
                $this->mappCombinedLogger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribeCustomer', __CLASS__, __FUNCTION__);

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $result->getSubscriberEmail(),
                    Subscriber::STATUS_SUBSCRIBED == $result->getSubscriberStatus()
                );

                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- Sent Event Via LEGACY Connect Client', __CLASS__, __FUNCTION__);
            }
        } catch (NoSuchEntityException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- The Customer Could Not Be Found', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        } catch (LocalizedException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @param $customerId
     * @return mixed
     * @throws LocalizedException
     */
    public function afterUnsubscribeCustomer($subject, $result, $customerId)
    {
        if (!$result || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        $this->mappCombinedLogger->info('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Unsubscribe Existing Customer', __CLASS__, __FUNCTION__);
        $this->mappCombinedLogger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribeCustomer', __CLASS__, __FUNCTION__);

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $result->getSubscriberEmail(),
                    Subscriber::STATUS_SUBSCRIBED == $result->getSubscriberStatus()
                );

                $this->mappCombinedLogger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- Sent Event Via LEGACY Connect Client', __CLASS__, __FUNCTION__);
            }
        } catch (NoSuchEntityException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- The Customer Could Not Be Found', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        } catch (GuzzleException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        } catch (LocalizedException $exception) {
            $this->mappCombinedLogger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', __CLASS__, __FUNCTION__, ['exception' => $exception]);
            $this->mappCombinedLogger->critical($exception->getTraceAsString(), __CLASS__, __FUNCTION__);
        }

        return $result;
    }

    /**
     * @return Client|null
     * @throws LocalizedException
     */
    private function getMappClient(): ?Client
    {
        return $this->connectHelper->getMappConnectClient();
    }
}
