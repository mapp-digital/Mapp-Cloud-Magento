<?php

namespace MappDigital\Cloud\Plugin\Sales\Model\ResourceModel;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Helper\Product;
use Magento\Customer\Model\Address\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\StorageInterface;
use Magento\Payment\Helper\Data;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Interceptor;
use Psr\Log\LoggerInterface;
use MappDigital\Cloud\Helper\Data as MappConnectHelper;

class OrderPlugin
{
    protected ScopeConfigInterface $scopeConfig;
    protected MappConnectHelper $helper;
    protected Product $productHelper;
    protected Config $addressConfig;
    protected Data $paymentHelper;
    protected StorageInterface $storage;
    protected LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        MappConnectHelper $helper,
        Product $productHelper,
        Config $addressConfig,
        Data $paymentHelper,
        StorageInterface $storage,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->productHelper = $productHelper;
        $this->addressConfig = $addressConfig;
        $this->paymentHelper = $paymentHelper;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    protected function getSelectedOptions($item)
    {
        $options = $item->getProductOptions();
        $options = array_merge(
            $options['options'] ?? [],
            $options['additional_options'] ?? [],
            $options['attributes_info'] ?? []
        );

        $formattedOptions = [];
        foreach ($options as $option) {
            $formattedOptions[] = $option['label'] . ': ' . $option['value'];
        }

        return implode(', ', $formattedOptions);
    }

    /**
     * @param $item
     * @return string
     */
    protected function getCategories($item): string
    {
        $categories = [];

        foreach ($item->getProduct()->getCategoryCollection()->addAttributeToSelect('name') as $category) {
            $categories[] = $category->getName();
        }

        return implode(', ', $categories);
    }

    /**
     * @param Order $subject
     * @param Interceptor $interceptor
     * @param OrderInterface $order
     * @return OrderInterface
     * @throws GuzzleException
     * @throws LocalizedException
     * @throws Exception
     */
    public function afterSave(Order $subject, Interceptor $interceptor, OrderInterface $order): OrderInterface
    {
        $transactionKey = 'mappconnect_transaction_' . $order->getId();

        if ($requireOrderStatusForExport = $this->helper->getConfigValue('export', 'transaction_send_on_status')) {
            if (!$order->dataHasChangedFor(OrderInterface::STATUS) || $order->getStatus() != $requireOrderStatusForExport) {
                return $order;
            }
        } else {
            //backward compatibility if config is not set
            if ($order->getState() != \Magento\Sales\Model\Order::STATE_NEW) {
                return $order;
            }
        }

        $this->logger->debug('Mapp Connect: Order plugin called');

        if ($this->helper->getConfigValue('export', 'transaction_enable') && $this->storage->getData($transactionKey) != true) {
            $data = $order->getData();
            $data['items'] = [];
            unset($data['status_histories'], $data['extension_attributes'], $data['addresses'], $data['payment']);

            foreach ($order->getAllVisibleItems() as $item) {
                $itemData = $item->getData();
                $itemData['base_image'] = $this->productHelper->getImageUrl($item->getProduct());
                $itemData['url_path'] = $item->getProduct()->getProductUrl();
                $itemData['categories'] = $this->getCategories($item);
                $itemData['manufacturer'] = $item->getProduct()->getAttributeText('manufacturer');

                $itemData['variant'] = $this->getSelectedOptions($item);
                unset($itemData['product_options'], $itemData['extension_attributes'], $itemData['parent_item']);

                $data['items'][] = $itemData;
            }

            if ($billingAddress = $order->getBillingAddress()) {
                $data['billingAddress'] = $billingAddress->getData();
            }

            if ($shippingAddress = $order->getShippingAddress()) {
                $data['shippingAddress'] = $shippingAddress->getData();
            }

            $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();

            $data['billingAddressFormatted'] = $renderer->renderArray($order->getBillingAddress());
            $data['shippingAddressFormatted'] = $renderer->renderArray($order->getShippingAddress());

            $data['payment_info'] = $this->paymentHelper->getInfoBlockHtml(
                $order->getPayment(),
                $data['store_id']
            );

            $messageId = $this->helper->templateIdToConfig('sales_email_order_template');

            if (isset($data['customer_is_guest']) && $data['customer_is_guest']) {
                $messageId = $this->helper->templateIdToConfig('sales_email_order_guest_template');
                if ($this->helper->getConfigValue('group', 'guests')) {
                    $data['group'] = $this->helper->getConfigValue('group', 'guests');
                }
            }

            if ($messageId) {
                $data['messageId'] = (string)$messageId;
            }

            try {
                if ($mappConnectClient = $this->helper->getMappConnectClient()) {
                    $mappConnectClient->event('transaction', $data);
                    $this->storage->setData($transactionKey, true);
                }
            } catch (Exception $e) {
                $this->logger->error('MappConnect: cannot sync transaction event', ['exception' => $e]);
            }
        } elseif ($this->helper->getConfigValue('export', 'customer_enable')) {
            $data = $order->getData();
            if (isset($data['customer_is_guest']) && $data['customer_is_guest']) {
                $data = [
                    'dob' => $order->getCustomerDob(),
                    'email' => $order->getCustomerEmail(),
                    'firstname' => $order->getCustomerFirstname(),
                    'gender' => $order->getCustomerGender(),
                    'lastname' => $order->getCustomerLastname(),
                    'middlename' => $order->getCustomerMiddlename(),
                    'note' => $order->getCustomerNote()
                ];

                $data['group'] = $this->helper->getConfigValue('group', 'guests');
                try {
                    if ($mappConnectClient = $this->helper->getMappConnectClient()) {
                        $this->logger->debug('MappConnect: sending guest customer', ['data' => $data]);
                        $mappConnectClient->event('user', $data);
                    }
                } catch (Exception $e) {
                    $this->logger->error('MappConnect: cannot sync guest customer', ['exception' => $e]);
                }
            }
        }

        return $order;
    }
}
