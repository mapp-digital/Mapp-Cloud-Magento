<?php

Namespace MappDigital\Cloud\Api;

interface NewsletterSubscriberInterface
{
    /**
     * @param string $email
     * @return \MappDigital\Cloud\Api\NewsletterSubscriberInterface
     */
    public function isEmailSubscribed(string $email);

    /**
     * @param string $email
     * @return \MappDigital\Cloud\Api\NewsletterSubscriberInterface
     */
    public function subscribe(string $email);

    /**
     * @param string $email
     * @return \MappDigital\Cloud\Api\NewsletterSubscriberInterface
     */
    public function unSubscribe(string $email);
}
