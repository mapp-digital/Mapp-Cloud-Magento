<?php

namespace MappDigital\Cloud\Plugin\Sales\Model\ResourceModel;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Interceptor;

class OrderPlugin
{
    protected $scopeConfig;
    protected $_helper;
    protected $productHelper;
    protected $addressConfig;
    protected $paymentHelper;
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MappDigital\Cloud\Helper\Data $helper,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Session\StorageInterface $storage,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_helper = $helper;
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
            isset($options['options']) ? $options['options'] : [],
            isset($options['additional_options']) ? $options['additional_options'] : [],
            isset($options['attributes_info']) ? $options['attributes_info'] : []
        );
        $ret = [];
        foreach ($options as $opt) {
            $ret[] = $opt['label'].': '.$opt['value'];
        }
        return implode(', ', $ret);
    }

    protected function getCategories($item)
    {
        $ret = [];
        foreach ($item->getProduct()->getCategoryCollection()->addAttributeToSelect('name') as $category) {
            $ret[]  = $category->getName();
        }
        return implode(', ', $ret);
    }

    public function afterSave(Order $subject, Interceptor $interceptor, OrderInterface $order): OrderInterface
    {
        $transaction_key = 'mappconnect_transaction_'.$order->getId();

        if ($sendonstatus = $this->_helper->getConfigValue('export', 'transaction_send_on_status')) {
          if (!$order->dataHasChangedFor(OrderInterface::STATUS)) {
            return $order;
          }
          if ($order->getStatus() != $sendonstatus) {
            return $order;
          }
        } else {
          //backward compatibility if config is not set
          if ($order->getState() != \Magento\Sales\Model\Order::STATE_NEW) {
            return $order;
          }
        }

        $this->logger->debug('MappConnect: Order plugin called');

        if ($this->_helper->getConfigValue('export', 'transaction_enable')
          && ($this->storage->getData($transaction_key) != true)) {
            $data = $order->getData();
            $data['items'] = [];
            unset($data['status_histories'], $data['extension_attributes'], $data['addresses'], $data['payment']);

            foreach ($order->getAllVisibleItems() as $item) {
                $item_data = $item->getData();
                $item_data['base_image'] = $this->productHelper->getImageUrl($item->getProduct());
                $item_data['url_path'] = $item->getProduct()->getProductUrl();
                $item_data['categories'] = $this->getCategories($item);
                $item_data['manufacturer'] = $item->getProduct()->getAttributeText('manufacturer');

                $item_data['variant'] = $this->getSelectedOptions($item);
                unset($item_data['product_options'], $item_data['extension_attributes'], $item_data['parent_item']);

                $data['items'][] = $item_data;
            }

            if ($billingAddress = $order->getBillingAddress())
                $data['billingAddress'] = $billingAddress->getData();
            if ($shippingAddress = $order->getShippingAddress())
                $data['shippingAddress'] = $shippingAddress->getData();

            $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();

            $data['billingAddressFormatted'] = $renderer->renderArray($order->getBillingAddress());
            $data['shippingAddressFormatted'] = $renderer->renderArray($order->getShippingAddress());

            $data['payment_info'] = $this->paymentHelper->getInfoBlockHtml(
                $order->getPayment(),
                $data['store_id']
            );

            $messageId = $this->_helper->templateIdToConfig("sales_email_order_template");

            if (isset($data['customer_is_guest']) && $data['customer_is_guest']) {
                $messageId = $this->_helper->templateIdToConfig("sales_email_order_guest_template");
                if ($this->_helper->getConfigValue('group', 'guests')) {
                    $data['group'] = $this->_helper->getConfigValue('group', 'guests');
                }
            }

            if ($messageId) {
                $data['messageId'] = (string)$messageId;
            }

            try {
                if ($mc = $this->_helper->getMappConnectClient()) {
                    $mc->event('transaction', $data);
                    $this->storage->setData($transaction_key, true);
                }
            } catch (\Exception $e) {
                $this->logger->error('MappConnect: cannot sync transaction event', ['exception' => $e]);
            }
        } elseif ($this->_helper->getConfigValue('export', 'customer_enable')) {
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

                $data['group'] = $this->_helper->getConfigValue('group', 'guests');
                try {
                    if ($mc = $this->_helper->getMappConnectClient()) {
                        $this->logger->debug('MappConnect: sending guest customer', ['data' => $data]);
                        $mc->event('user', $data);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('MappConnect: cannot sync guest customer', ['exception' => $e]);
                }
            }
        }
        return $order;
    }
}
