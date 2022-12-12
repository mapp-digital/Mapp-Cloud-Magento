<?php
namespace MappDigital\Cloud\Plugin;

class SubscriptionManagerPlugin
{

    protected $scopeConfig;
    protected $customerRepository;
    protected $helper;
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \MappDigital\Cloud\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository= $customerRepository;
        $this->_helper = $helper;
        $this->logger = $logger;
    }

    public function afterSubscribe($subject, $result, $email)
    {
        if (!$result) {
            return $result;
        }
        $this->logger->debug('MappConnect: SubscribeManager subscribe');
        try {
            if (($mappconnect = $this->_helper->getMappConnectClient())
            && $this->_helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->_helper->getConfigValue('group', 'subscribers')
                ];
                if ($this->_helper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }
                $this->logger->debug('MappConnect: sending newsletter', ['data' => $data]);
                $mappconnect->event('newsletter', $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('MappConnect: cannot sync subscribe event', ['exception' => $e]);
        }
        return $result;
    }

    public function afterUnsubscribe($subject, $result)
    {
        if (!$result) {
            return $result;
        }
        $this->logger->debug('MappConnect: SubscribeManager unsubscribe');
        $email = $subject->getEmail();
        try {
            if (($mappconnect = $this->_helper->getMappConnectClient())
            && $this->_helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->_helper->getConfigValue('group', 'subscribers'),
                 'unsubscribe' => true
                ];
                $this->logger->debug('MappConnect: sending newsletter', ['data' => $data]);
                $mappconnect->event('newsletter', $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('MappConnect: cannot sync unsubscribe event', ['exception' => $e]);
        }
        return $result;
    }

    public function afterSubscribeCustomer($subject, $result, $customerId)
    {
        if (!$result) {
            return $result;
        }
        $this->logger->debug('MappConnect: SubscribeManager subscribe');
        $customer = $this->customerRepository->getById($customerId);
        $email = $customer->getEmail();
        try {
            if (($mappconnect = $this->_helper->getMappConnectClient())
            && $this->_helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->_helper->getConfigValue('group', 'subscribers')
                ];
                if ($this->_helper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }
                $this->logger->debug('MappConnect: sending newsletter', ['data' => $data]);
                $mappconnect->event('newsletter', $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('MappConnect: cannot sync subscribe event', ['exception' => $e]);
        }
        return $result;
    }

    public function afterUnsubscribeCustomer($subject, $result, $customerId)
    {
        if (!$result) {
            return $result;
        }
        $this->logger->debug('MappConnect: SubscribeManager unsubscribe');
        $customer = $this->customerRepository->getById($customerId);
        $email = $customer->getEmail();
        try {
            if (($mappconnect = $this->_helper->getMappConnectClient())
            && $this->_helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->_helper->getConfigValue('group', 'subscribers'),
                 'unsubscribe' => true
                ];
                $this->logger->debug('MappConnect: sending newsletter', ['data' => $data]);
                $mappconnect->event('newsletter', $data);
            }
        } catch (\Exception $e) {
            $this->logger->error('MappConnect: cannot sync unsubscribe event', ['exception' => $e]);
        }

        return $result;
    }
}
