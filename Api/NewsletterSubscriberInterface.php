<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
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
