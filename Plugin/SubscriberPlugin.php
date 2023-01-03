<?php
namespace MappDigital\Cloud\Plugin;

use Closure;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use Psr\Log\LoggerInterface;

class SubscriberPlugin
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
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Subscribe');
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribe');

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
                ];

                if ($this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client',
                    ['data' => $data]
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (Exception $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
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
        if (!$result->isStatusChanged() || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Guest Newsletter Unsubscribe');
                $this->logger->debug('Mapp Connect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribe');

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers'),
                    'unsubscribe' => true
                ];

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client',
                    ['data' => $data]
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (Exception $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
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
    public function afterSubscribeCustomerById($subject, $result)
    {
        if (!$result->isStatusChanged() || !$this->connectHelper->isLegacySyncEnabled()) {
            return $result;
        }

        try {
            if ($this->connectHelper->getConfigValue('export', 'newsletter_enable')) {
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Subscribe Existing Customer');
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: subscribeCustomerById');

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers')
                ];

                if ($this->connectHelper->getConfigValue('export', 'newsletter_doubleoptin')) {
                    $data['doubleOptIn'] = true;
                }

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client',
                    ['data' => $data]
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (Exception $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
            $this->logger->error($exception);
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
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Newsletter Unsubscribe Existing Customer');
                $this->logger->debug('MappConnect: -- PLUGIN INTERCEPTOR -- After Base Method: unsubscribeCustomerById');

                $data = [
                    'email' => $subject->getEmail(),
                    'group' => $this->connectHelper->getConfigValue('group', 'subscribers'),
                    'unsubscribe' => true
                ];

                $this->logger->debug(
                    'MappConnect: -- PLUGIN INTERCEPTOR -- Newsletter Send Event Via Connect Client',
                    ['data' => $data]
                );

                $this->connectHelper->getMappConnectClient()->event('newsletter', $data);
            }
        } catch (GuzzleException $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
            $this->logger->error($exception);
        } catch (Exception $exception) {
            $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
            $this->logger->error($exception);
        }

        return $result;
    }
}
