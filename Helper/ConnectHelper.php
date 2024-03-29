<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Helper;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use MappDigital\Cloud\Enum\Connect\ConfigurationPaths;
use MappDigital\Cloud\Model\Config\Source\SyncMethod;
use MappDigital\Cloud\Model\Connect\Client as MappConnectClient;
use MappDigital\Cloud\Model\Connect\ClientFactory as MappConnectClientFactory;

class ConnectHelper extends AbstractHelper
{
    const CONFIG_PREFIX = 'mapp_connect';
    const XML_PATH_ORDER_RETRY_LIMIT = 'mapp_connect/export/transaction_retry_max';

    protected ?MappConnectClient $client = null;

    public function __construct(
        protected Http $request,
        protected ScopeConfigInterface $config,
        protected State $state,
        protected MappConnectClientFactory $mappConnectClientFactory,
        protected StoreManager $storeManager,
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getBaseURL(): string
    {
        $url = $this->getConfigValue('general', 'base_url');

        if ($url == 'custom') {
            $url = $this->getConfigValue('general', 'base_url_custom');
        }

        return $url;
    }

    /**
     * @return MappConnectClient|null
     * @throws LocalizedException
     */
    public function getMappConnectClient(): ?MappConnectClient
    {
        if ($this->client === null) {
            if ($this->getConfigValue('integration', 'integration_enable')) {
                $this->client = $this->mappConnectClientFactory->create();
            }
        }

        return $this->client;
    }

    /**
     * @param string $group
     * @param string $field
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     */
    public function getConfigValue(string $group, string $field, ?int $storeId = null): string
    {
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML && is_null($storeId)) {
            if ($storeIdParam = $this->request->getParam('store')) {
                return (string)$this->config->getValue(
                    self::CONFIG_PREFIX . '/' . $group . '/' . $field,
                    ScopeInterface::SCOPE_STORE,
                    $storeIdParam
                );
            }

            if ($websiteIdParam = $this->request->getParam('website')) {
                return (string)$this->config->getValue(
                    self::CONFIG_PREFIX . '/' . $group . '/' . $field,
                    ScopeInterface::SCOPE_WEBSITE,
                    $websiteIdParam
                );
            }
        }

        return (string)$this->config->getValue(
            self::CONFIG_PREFIX . '/' . $group . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeManager->getStore()?->getId() ?? null
        );
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isLegacySyncEnabled(): bool
    {
        return (bool) $this->getConfigValue('export', 'sync_method') == SyncMethod::SYNC_METHOD_LEGACY;
    }

    /**
     * @return bool
     */
    public function isMappEmailEnabled(): bool
    {
        return (bool)$this->config->getValue(ConfigurationPaths::XML_PATH_EMAILS_ENABLED->value, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $templateId
     * @return int|string
     * @throws LocalizedException
     */
    public function templateIdToConfig($templateId, $storeId = null): int|string
    {
        $map = [
            'sales_email_order_template' => 'mapp_connect_messages/order/template',
            'sales_email_order_guest_template' => 'mapp_connect_messages/order/guest_template',
            'sales_email_order_comment_template' => "mapp_connect_messages/order_comment/template",
            'sales_email_order_comment_guest_template' => 'mapp_connect_messages/order_comment/guest_template',
            'sales_email_invoice_template' => 'mapp_connect_messages/invoice/template',
            'sales_email_invoice_guest_template' => 'mapp_connect_messages/invoice/guest_template',
            'sales_email_invoice_comment_template' => 'mapp_connect_messages/invoice_comment/template',
            'sales_email_invoice_comment_guest_template' => 'mapp_connect_messages/invoice_comment/guest_template',
            'sales_email_shipment_template' => 'mapp_connect_messages/shipment/template',
            'sales_email_shipment_guest_template' => 'mapp_connect_messages/shipment/guest_template',
            'sales_email_shipment_comment_template' => 'mapp_connect_messages/shipment_comment/template',
            'sales_email_shipment_comment_guest_template' => 'mapp_connect_messages/shipment_comment/guest_template',
            'sales_email_creditmemo_template' => 'mapp_connect_messages/creditmemo/template',
            'sales_email_creditmemo_guest_template' => 'mapp_connect_messages/creditmemo/guest_template',
            'sales_email_creditmemo_comment_template' => 'mapp_connect_messages/creditmemo_comment/template',
            'sales_email_creditmemo_comment_guest_template' => 'mapp_connect_messages/creditmemo_comment/guest_template',
            'customer_create_account_email_template' => 'mapp_connect_messages/create_account/email_template',
            'customer_create_account_email_no_password_template' => 'mapp_connect_messages/create_account/email_no_password_template',
            'customer_create_account_email_confirmation_template' => 'mapp_connect_messages/create_account/email_confirmation_template',
            'customer_create_account_email_confirmed_template' => 'mapp_connect_messages/create_account/email_confirmed_template',
            'customer_password_forgot_email_template' => 'mapp_connect_messages/password/forgot_email_template',
            'customer_password_remind_email_template' => 'mapp_connect_messages/password/remind_email_template',
            'customer_password_reset_password_template' => 'mapp_connect_messages/password/reset_password_template',
            'customer_account_information_change_email_template' => 'mapp_connect_messages/account_information/change_email_template',
            'customer_account_information_change_email_and_password_template' => 'mapp_connect_messages/account_information/change_email_and_password_template',
            'sendfriend_email_template' => 'mapp_connect_messages/sendfriend/template',
            'catalog_productalert_email_price_template' => 'mapp_connect_messages/productalert/email_price_template',
            'catalog_productalert_email_stock_template' => 'mapp_connect_messages/productalert/email_stock_template',
            'wishlist_email_email_template' => 'mapp_connect_messages/wishlist/email_template',
            'sales_email_order_cancel_template' => 'mapp_connect_messages/order/cancellation_template',
            'sales_email_order_cancel_guest_template' => 'mapp_connect_messages/order/guest_cancellation_template'
        ];

        if (!array_key_exists($templateId, $map)) {
            return 0;
        }

        if (is_null($storeId) && $this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            if ($storeId = $this->request->getParam('store')) {
                return (string)$this->config->getValue(
                    $map[$templateId],
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );
            }

            if ($websiteId = $this->request->getParam('website')) {
                return (string)$this->config->getValue(
                    $map[$templateId],
                    ScopeInterface::SCOPE_WEBSITE,
                    $websiteId
                );
            }
        }

        return (int)$this->config->getValue($map[$templateId],
            ScopeInterface::SCOPE_STORE,
            $storeId ?? $this->storeManager->getStore()?->getId() ?? null
        );
    }

    /**
     * @param $path
     * @return string
     */
    public function getConfigValueByPath($path): string
    {
        return $this->config->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
