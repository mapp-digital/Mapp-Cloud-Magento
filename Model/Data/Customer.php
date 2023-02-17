<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Data;

use Magento\Customer\Model\Address;
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
    protected ?Address $billingAddress = null;
    protected ?Address $shippingAddress = null;

    /**
     * @param Session $customerSession
     */
    public function __construct(
        Session $customerSession
    ) {
        $this->_customerSession = $customerSession;
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

    // -----------------------------------------------
    // SETTERS AND GETTERS
    // -----------------------------------------------

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
    private function getTelephoneHashes(): array
    {
        $telephone = $this->validate($this->billingAddress->getTelephone(), '/(\s|\D)/');
        $telephoneHashes = [];

        if ($telephone) {
            $telephoneHashes['md5'] = hash('md5', $telephone);
            $telephoneHashes['sha256'] = hash('sha256', $telephone);
        }

        return $telephoneHashes;
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
        $addresses = [];

        if ($this->_customer->getPrimaryBillingAddress()) {
            $this->billingAddress = $this->_customer->getPrimaryBillingAddress();
            $addresses['billing'] = $this->billingAddress->getData();
        }

        if ($this->_customer->getPrimaryShippingAddress()) {
            $this->shippingAddress = $this->_customer->getPrimaryShippingAddress();
            $addresses['shipping'] = $this->shippingAddress->getData();
        }

        $this->set('address', $addresses);
    }

    private function setCDBData()
    {
        $cdbData = [];
        $cdbData['email'] = $this->getEmailHashes();

        if ($this->billingAddress) {
            $cdbData['telephone'] = $this->getTelephoneHashes();
            $cdbData['address'] = $this->getAddressHashes();
        }

        $this->set('CDB', $cdbData);
    }

    /**
     * @return array
     */
    private function getAddressHashes()
    {
        // @format '<Firstname>|<Lastname>|<Postcode>|<Street>|<Streetnumber>'
        $addressStrings = [];

        if ($this->billingAddress) {
            $addressStrings[] = $this->billingAddress->getFirstname();
            $addressStrings[] = $this->billingAddress->getLastname();
            $addressStrings[] = $this->billingAddress->getPostcode();
            $addressStrings[] = implode('', $this->billingAddress->getStreet());
        }

        $address = $this->validate(implode('|', $addressStrings), self::ADDRESS_PATTERN, self::ADDRESS_REPLACEMENT);
        $addressHashes = [];

        if ($address) {
            $addressHashes['md5'] = hash('md5', $address);
            $addressHashes['sha256'] = hash('sha256', $address);
        }

        return $addressHashes;
    }

    /**
     * @return array
     */
    public function getDataLayer(): array
    {
        $this->generate();

        return $this->_data ?? [];
    }

    // -----------------------------------------------
    // UTILITY
    // -----------------------------------------------

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
}
