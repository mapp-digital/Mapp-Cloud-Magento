<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Observer;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use MappDigital\Cloud\Helper\Config;
use MappDigital\Cloud\Helper\ConnectHelper;
use MappDigital\Cloud\Helper\DataLayer as DataLayerHelper;
use MappDigital\Cloud\Logger\CombinedLogger;
use MappDigital\Cloud\Model\Connect\SubscriptionManager;
use MappDigital\Cloud\Model\Data\Product as MappProductModel;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

abstract class TIDatalayerCartAbstract implements ObserverInterface
{
    public function __construct(
        protected Session $checkoutSession,
        protected Config $config,
        protected MappProductModel $mappProductModel,
        protected ProductAttributeRepositoryInterface $productAttributeRepositoryInterface,
        protected Http $request,
        protected ConnectHelper $connectHelper,
        protected SubscriptionManager $subscriptionManager,
        protected CustomerSession $customerSession,
        protected PublisherInterface $publisher,
        protected DeploymentConfig $deploymentConfig,
        protected CombinedLogger $mappCombinedLogger,
        protected Json $jsonSerializer,
        protected WishlistItem $wishlistItem,
        protected ProductRepositoryInterface $productRepository,
        protected TimezoneInterface $timezoneInterface
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            if (($product = $this->mappProductModel->getProduct()) && $product->hasData()) {
                $this->checkoutSession->setData('webtrekk_addproduct', DataLayerHelper::merge($this->getSessionData('webtrekk_addproduct'), $this->getProductData()));
            }
        }
    }

    /**
     * @param Item $item
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductData(Item $item)
    {
        if ($this->config->isEnabled()) {
            if (($product = $this->mappProductModel->getProduct()) && $product->hasData()) {
                try {
                    $urlFragment = DataLayerHelper::getUrlFragment($product);
                    $productData = $this->mappProductModel->getDataLayer($urlFragment);
                    $productData['qty'] = intval($item->getQtyToAdd());
                    $productData['quantity'] = intval($item->getQtyToAdd());
                    $productData['status'] = 'add';
                    $allAttributesForProduct = [];

                    if ($item->getProductType() === 'configurable') {
                        $selectedOptions = json_decode($item->getOptionsByCode()['attributes']->getValue(), true);
                        foreach ($selectedOptions as $attributeCodeId => $optionValueId) {
                            $attributeRepo = $this->productAttributeRepositoryInterface->get($attributeCodeId);
                            $allAttributesForProduct[$attributeRepo->getAttributeCode()] = $attributeRepo->getSource()->getOptionText($optionValueId);
                        }
                    }

                    $productData['attributes'] = $allAttributesForProduct;
                    $productData['price'] = $item->getPrice();
                    $productData['cost'] = $item->getPrice();
                    $productData['sku'] = $item->getSku();
                    $productData['name'] = $item->getName();
                    $productData['weight'] = $item->getWeight();

                    try {
                        $productData['currency'] = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
                    } catch (NoSuchEntityException $exception) {
                    }

                    return $productData;
                } catch (NoSuchEntityException $exception) {
                    return [];
                }
            }
        }

        return [];
    }

    /**
     * @param string $key
     * @return array
     */
    protected function getSessionData(string $key): array
    {
        return $this->checkoutSession->getData($key) ?? [];
    }

    /**
     * @return string
     */
    public function getAbandonedCartPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType, __CLASS__, __FUNCTION__);
        return 'mappdigital.cloud.entities.campaigns.abandoned.cart.' . $queueType;
    }

    /**
     * @return string
     */
    public function getWishlistPublisherName(): string
    {
        $queueType = $this->isAmqp() ? 'amqp' : 'db';
        $this->mappCombinedLogger->debug('MappConnect: -- SubscriptionManager -- Using Consumer Queue Type Of: ' . $queueType, __CLASS__, __FUNCTION__);
        return 'mappdigital.cloud.entities.campaigns.wishlist.' . $queueType;
    }

    /**
     * Check if Amqp is used
     *
     * @return bool
     */
    protected function isAmqp(): bool
    {
        try {
            return (bool)$this->deploymentConfig->get('queue/amqp');
        } catch (\Exception $exception) {
            return false;
        }
    }
}
