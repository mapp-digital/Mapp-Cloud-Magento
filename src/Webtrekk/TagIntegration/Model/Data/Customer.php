<?php
/**
 * @author Webtrekk Team
 * @copyright Copyright (c) 2016 Webtrekk GmbH (https://www.webtrekk.com)
 * @package Webtrekk_TagIntegration
 */
namespace Webtrekk\TagIntegration\Model\Data;

use Magento\Customer\Model\Session;

class Customer extends AbstractData
{

    /**
     * @var array
     */
    const ADDRESS_PATTERN = [
        '/ä/', '/ö/', '/ü/', '/ß/', '/[\s_\-]/', '/str(\.)?(\s|\|)/'
    ];
    /**
     * @var array
     */
    const ADDRESS_REPLACEMENT = [
        'ae', 'oe', 'ue', 'ss', '', 'strasse|'
    ];

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;
    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $_billingAddress;
    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $_shippingAddress;

    /**
     * @param Session $customerSession
     */
    public function __construct(Session $customerSession)
    {
        $this->_customerSession = $customerSession;
    }

    /**
     * @param string $value
     * @param string $pattern
     * @param string $replacement
     *
     * @return string
     */
    private function validate($value = '', $pattern = '', $replacement = '')
    {
        $validatedValue = '';
        if ($value) {
            $validatedValue = strtolower($value);

            if ($pattern) {
                $validatedValue = preg_replace($pattern, $replacement, $validatedValue);
            }
        }

        return $validatedValue;
    }

    /**
     * @return array
     */
    private function getEmailHashes()
    {
        $email = $this->validate($this->_customer->getEmail(), '/\s/');
        $emailHashes = [];

        if ($email) {
            $emailHashes['md5'] = hash('md5', $email);
            $emailHashes['sha256'] = hash('sha256', $email);
        }

        return $emailHashes;
    }

    /**
     * @return array
     */
    private function getTelephoneHashes()
    {
        $telephone = $this->validate($this->_billingAddress->getTelephone(), '/(\s|\D)/');
        $telephoneHashes = [];

        if ($telephone) {
            $telephoneHashes['md5'] = hash('md5', $telephone);
            $telephoneHashes['sha256'] = hash('sha256', $telephone);
        }

        return $telephoneHashes;
    }

    /**
     * @return array
     */
    private function getAddressHashes()
    {
        // @format '<Firstname>|<Lastname>|<Postcode>|<Street>|<Streetnumber>'
        $addressStrings = [];
        $addressStrings[] = $this->_billingAddress->getFirstname();
        $addressStrings[] = $this->_billingAddress->getLastname();
        $addressStrings[] = $this->_billingAddress->getPostcode();
        $addressStrings[] = implode('', $this->_billingAddress->getStreet());

        $address = $this->validate(implode('|', $addressStrings), self::ADDRESS_PATTERN, self::ADDRESS_REPLACEMENT);
        $addressHashes = [];

        if ($address) {
            $addressHashes['md5'] = hash('md5', $address);
            $addressHashes['sha256'] = hash('sha256', $address);
        }

        return $addressHashes;
    }

    private function setAttributes()
    {
        $customerAttributes = $this->_customer->getData();

        foreach ($customerAttributes as $code => $attribute) {
            $this->set($code, $attribute);
        }
    }

    private function setAddresses()
    {
        $this->_billingAddress = $this->_customer->getPrimaryBillingAddress();
        $this->_shippingAddress = $this->_customer->getPrimaryShippingAddress();

        $addresses = [];

        if ($this->_billingAddress) {
            $addresses['billing'] = $this->_billingAddress->getData();
        }
        if ($this->_shippingAddress) {
            $addresses['shipping'] = $this->_shippingAddress->getData();
        }

        $this->set('address', $addresses);
    }

    private function setCDBData()
    {
        $cdbData = [];
        $cdbData['email'] = $this->getEmailHashes();

        if ($this->_billingAddress) {
            $cdbData['telephone'] = $this->getTelephoneHashes();
            $cdbData['address'] = $this->getAddressHashes();
        }

        $this->set('CDB', $cdbData);
    }

    private function generate()
    {
        if ($this->_customerSession->isLoggedIn()) {
            $this->_customer = $this->_customerSession->getCustomer();

            $this->setAttributes();
            $this->setAddresses();
            $this->setCDBData();
        }
    }

    /**
     * @return array
     */
    public function getDataLayer()
    {
        $this->generate();

        return $this->_data;
    }
}
