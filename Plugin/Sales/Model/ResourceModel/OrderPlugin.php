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
use MappDigital\Cloud\Helper\ConnectHelper;

class OrderPlugin
{
    protected ScopeConfigInterface $scopeConfig;
    protected ConnectHelper $connectHelper;
    protected Product $productHelper;
    protected Config $addressConfig;
    protected Data $paymentHelper;
    protected StorageInterface $storage;
    protected LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConnectHelper $connectHelper,
        Product $productHelper,
        Config $addressConfig,
        Data $paymentHelper,
        StorageInterface $storage,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectHelper = $connectHelper;
        $this->productHelper = $productHelper;
        $this->addressConfig = $addressConfig;
        $this->paymentHelper = $paymentHelper;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * @param $item
     * @return string
     */
    protected function getSelectedOptions($item): string
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
        $transactionKey = 'mapp_connect_transaction_' . $order->getId();

        if ($requireOrderStatusForExport = $this->connectHelper->getConfigValue('export', 'transaction_send_on_status')) {
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

        if ($this->connectHelper->getConfigValue('export', 'transaction_enable') && $this->storage->getData($transactionKey) != true) {
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

            $messageId = $this->connectHelper->templateIdToConfig('sales_email_order_template');

            if (isset($data['customer_is_guest']) && $data['customer_is_guest']) {
                $messageId = $this->connectHelper->templateIdToConfig('sales_email_order_guest_template');
                if ($this->connectHelper->getConfigValue('group', 'guests')) {
                    $data['group'] = $this->connectHelper->getConfigValue('group', 'guests');
                }
            }

            if ($messageId) {
                $data['messageId'] = (string)$messageId;
            }

            try {
                $this->connectHelper->getMappConnectClient()->event('transaction', $data);
                $this->storage->setData($transactionKey, true);
            } catch (GuzzleException $exception) {
                $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
                $this->logger->error($exception);
            } catch (Exception $exception) {
                $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
                $this->logger->error($exception);
            }

        } elseif ($this->connectHelper->getConfigValue('export', 'customer_enable')) {
            $data = $order->getData();
            if (isset($data['customer_is_guest']) && $data['customer_is_guest']) {

                try {
                    $data = [
                        'dob' => $order->getCustomerDob(),
                        'email' => $order->getCustomerEmail(),
                        'firstname' => $order->getCustomerFirstname(),
                        'gender' => $order->getCustomerGender(),
                        'lastname' => $order->getCustomerLastname(),
                        'middlename' => $order->getCustomerMiddlename(),
                        'note' => $order->getCustomerNote()
                    ];

                    $data['group'] = $this->connectHelper->getConfigValue('group', 'guests');

                    $this->logger->debug('MappConnect: sending guest customer', ['data' => $data]);
                    $this->connectHelper->getMappConnectClient()->event('user', $data);
                } catch (GuzzleException $exception) {
                    $this->logger->error('Mapp Connect -- ERROR -- Connection Could Not Be Made To Connect', ['exception' => $exception]);
                    $this->logger->error($exception);
                } catch (Exception $exception) {
                    $this->logger->error('Mapp Connect -- ERROR -- A General Error Has Occurred', ['exception' => $exception]);
                    $this->logger->error($exception);
                }
            }
        }

        return $order;
    }
}
