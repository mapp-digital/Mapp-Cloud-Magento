<?php
namespace MappDigital\Cloud\Plugin;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MappDigital\Cloud\Model\Connect\Client;
use Psr\Log\LoggerInterface;
use MappDigital\Cloud\Helper\ConnectHelper;

class SubscriptionManagerPlugin
{
    protected ScopeConfigInterface $scopeConfig;
    protected CustomerRepositoryInterface $customerRepository;
    protected ConnectHelper $connectHelper;
    protected LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        ConnectHelper $connectHelper,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository= $customerRepository;
        $this->connectHelper = $connectHelper;
        $this->logger = $logger;
    }

    public function afterSubscribe($subject, $result, $email)
    {
        if (!$result) {
            return $result;
        }

        $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Subscribe');
        $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribe');

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
                ];

                if ($this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- Double Opt In Added');
                    $data['doubleOptIn'] = true;
                }

                $this->getMappClient()->event('newsletter', $data);

                $this->logger->debug(
                    'Mapp Connect: -- PLUGIN INTERCEPTOR -- Sent Event Via Connect Client',
                    ['data' => $data]
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
     */
    public function afterUnsubscribe($subject, $result)
    {
        if (!$result) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Unsubscribe');
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribe');

                $data = [
                    'email' => $subject->getEmail() ?? '',
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers'),
                    'unsubscribe' => true
                ];

                $this->logger->debug('MappConnect: sending newsletter', ['data' => $data]);
                $this->getMappClient()->event('newsletter', $data);
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
     */
    public function afterSubscribeCustomer($subject, $result, $customerId)
    {
        if (!$result) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Subscribe Existing Customer');
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribeCustomer');

                $customer = $this->customerRepository->getById($customerId);
                $email = $customer->getEmail();

                $data = [
                    'email' => $email,
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
                ];

                if ($this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client',
                    ['data' => $data]
                );

                $this->getMappClient()->event('newsletter', $data);
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
     */
    public function afterUnsubscribeCustomer($subject, $result, $customerId)
    {
        if (!$result) {
            return $result;
        }

        $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Unsubscribe Existing Customer');
        $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribeCustomer');

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $customer = $this->customerRepository->getById($customerId);

                $data = [
                    'email' => $customer->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers'),
                    'unsubscribe' => true
                ];

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Unsubscribe Event Sent Via Connect Client',
                    ['data' => $data]
                );

                $this->logger->debug('MappConnect: sending newsletter', ['data' => $data]);
                $this->getMappClient()->event('newsletter', $data);
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
