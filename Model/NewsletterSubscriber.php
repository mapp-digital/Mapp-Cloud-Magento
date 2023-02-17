<?php

namespace MappDigital\Cloud\Model;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManager;
use Magento\Store\Model\StoreManagerInterface;
use MappDigital\Cloud\Api\NewsletterSubscriberInterface;

class NewsletterSubscriber implements NewsletterSubscriberInterface
{
    private SubscriberFactory $subscriberFactory;
    private CustomerFactory $customerFactory;
    private StoreManagerInterface $storeManager;
    private EmailValidator $emailValidator;
    private SubscriptionManager $subscriptionManager;
    private Json $jsonSerializer;

    public function __construct(
        SubscriberFactory $subscriberFactory,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        EmailValidator $emailValidator,
        SubscriptionManager $subscriptionManager,
        Json $jsonSerializer
    ){
        $this->subscriberFactory = $subscriberFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->emailValidator = $emailValidator;
        $this->subscriptionManager = $subscriptionManager;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param string $email
     * @return NewsletterSubscriberInterface
     * @throws LocalizedException
     */
    public function isEmailSubscribed(string $email)
    {
        $this->validateEmailFormat($email);
        $isSubscribed = false;

        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberFactory->create()->loadBySubscriberEmail(
            $email,
            $this->storeManager->getWebsite()->getId()
        );

        if ($subscriber->isSubscribed()) {
            $isSubscribed = true;
        }

        return $this->jsonSerializer->serialize([
            'subscribed' => $isSubscribed
        ]);
    }

    /**
     * @param $email
     * @return NewsletterSubscriberInterface
     */
    public function subscribe($email)
    {
        $success = false;

        try {
            $this->validateEmailFormat($email);

            $subscriber = $this->subscriberFactory->create()->loadByEmail($email);
            if ($subscriber->getId()
                && $subscriber->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED
            ) {
                throw new LocalizedException(
                    __('This email address is already subscribed.')
                );
            }

            $subscriber = $this->subscriptionManager->subscribe($email, $this->storeManager->getStore()->getId());
            if ($subscriber->getStatus() == Subscriber::STATUS_NOT_ACTIVE) {
                $success = true;
                $message = __('The confirmation request has been sent.');
            } else {
                $success = true;
                $message = __('Email has been subscribed.');
            }
        } catch (LocalizedException $e) {
            $message = __('There was a problem with the subscription: %1', $e->getMessage());
        }

        return $this->jsonSerializer->serialize([
            'success' => $success,
            'message' => $message ?? '',
        ]);
    }

    /**
     * @param string $email
     * @return bool|NewsletterSubscriberInterface|string
     * @throws LocalizedException
     */
    public function unSubscribe(string $email)
    {
        $this->validateEmailFormat($email);
        $success = false;

        try {
            $subscriber = $this->subscriberFactory->create()->loadByEmail($email);
            $subscriber = $this->subscriptionManager->unsubscribe($subscriber->getEmail(), $this->storeManager->getStore()->getId(), $subscriber->getSubscriberConfirmCode());

            if ($subscriber->getStatus() == Subscriber::STATUS_UNSUBSCRIBED) {
                $success = true;
                $message = "Subscriber with email of {$email} has been unsubscribed";
            } else {
                $message = "Newsletter Subscriber was not unsubscribed. Confirm if they were subscribed to begin with. Subscriber Status is {$subscriber->getStatus()}";
            }
        } catch (LocalizedException $e) {
            $message = __('There was a problem with the subscription: %1', $e->getMessage());
        }

        return $this->jsonSerializer->serialize([
            'success' => $success,
            'message' => $message ?? '',
        ]);
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @throws LocalizedException
     * @return void
     */
    protected function validateEmailFormat($email)
    {
        if (!$this->emailValidator->isValid($email)) {
            throw new LocalizedException(__('Please submit a valid email address.'));
        }
    }
}
