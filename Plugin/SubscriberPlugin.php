<?php
namespace MappDigital\Cloud\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class SubscriberPlugin
{

    protected ScopeConfigInterface $scopeConfig;
    protected $helper;
    protected LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \MappDigital\Cloud\Helper\Data $helper,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function afterSubscribe($subject, $result)
    {
        if (!$result) {
            return $result;
        }
        $email = $subject->getEmail();
        $this->logger->debug('MappConnect: Subscribe subscribe called');
        try {
            if (($mappconnect = $this->helper->getMappConnectClient())
            && $this->helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->helper->getConfigValue('group', 'subscribers')
                ];
                if ($this->helper->getConfigValue('export', 'newsletter_doubleoptin')) {
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
        if (!($result->isStatusChanged())) {
            return $result;
        }
        $this->logger->debug('MappConnect: Subscribe unsubscribe called');
        $email = $subject->getEmail();
        try {
            if (($mappconnect = $this->helper->getMappConnectClient())
            && $this->helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->helper->getConfigValue('group', 'subscribers'),
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

    public function afterSubscribeCustomerById($subject, $result)
    {
        if (!($result->isStatusChanged())) {
            return $result;
        }
        $this->logger->debug('MappConnect: Subscribe subscribe by customerid called');
        $email = $subject->getEmail();
        try {
            if (($mappconnect = $this->helper->getMappConnectClient())
            && $this->helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->helper->getConfigValue('group', 'subscribers')
                ];
                if ($this->helper->getConfigValue('export', 'newsletter_doubleoptin')) {
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

    public function aroundUnsubscribeCustomerById($subject, \Closure $proceed, $customerId)
    {
        $result = $proceed($customerId);
        if (!($subject->isStatusChanged())) {
            return $result;
        }

        $this->logger->debug('MappConnect: Subscribe unsubscribe by customerid called');
        $email = $subject->getEmail();
        try {
            if (($mappconnect = $this->helper->getMappConnectClient())
            && $this->helper->getConfigValue('export', 'newsletter_enable')) {
                $data = [
                 'email' => $email,
                 'group' => $this->helper->getConfigValue('group', 'subscribers'),
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
