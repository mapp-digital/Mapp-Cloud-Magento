<?php
namespace MappDigital\Cloud\Plugin;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use MappDigital\Cloud\Model\Connect\Client;
use Psr\Log\LoggerInterface;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;

class SubscriptionManagerPlugin
{
    protected ScopeConfigInterface $scopeConfig;
    protected CustomerRepositoryInterface $customerRepository;
    protected ConnectHelper $connectHelper;
    protected LoggerInterface $logger;
    protected SubscriptionManager $subscriptionManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        ConnectHelper $connectHelper,
        SubscriptionManager $subscriptionManager,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository= $customerRepository;
        $this->connectHelper = $connectHelper;
        $this->subscriptionManager = $subscriptionManager;
        $this->logger = $logger;
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
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Subscribe');
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribe');
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- Sent Event Via Connect Client');

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $email,
                    true
                );
            }
        } catch (\Exception | GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Failure to send guest newsletter via connect API', ['exception' => $exception]);
            $this->logger->error($exception);
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
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Unsubscribe');
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribe');

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $subject->getEmail(),
                    false
                );
            }
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (LocalizedException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
            $this->logger->error($exception);
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
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Subscribe Existing Customer');
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribeCustomer');
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client');

                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $result->getSubscriberEmail(),
                    Subscriber::STATUS_SUBSCRIBED == $result->getSubscriberStatus()
                );
            }
        } catch (NoSuchEntityException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- The Customer Could Not Be Found', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (LocalizedException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
            $this->logger->error($exception);
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

        $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Unsubscribe Existing Customer');
        $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribeCustomer');

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->subscriptionManager->sendNewsletterSubscriptionUpdate(
                    $result->getSubscriberEmail(),
                    Subscriber::STATUS_SUBSCRIBED == $result->getSubscriberStatus()
                );

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Unsubscribe Event Sent Via Connect Client'
                );
            }
        } catch (NoSuchEntityException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- The Customer Could Not Be Found', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (LocalizedException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
            $this->logger->error($exception);
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
