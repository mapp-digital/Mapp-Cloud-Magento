<?php

namespace MappDigital\Cloud\Plugin\Mail;

class TransportBuilderByStorePlugin
{

    private $senderResolver;
    public function __construct(\Magento\Framework\Mail\Template\SenderResolverInterface $senderResolver)
    {
        $this->senderResolver = $senderResolver;
    }

    public function beforeSetFromByStore(
        \Magento\Framework\Mail\Template\TransportBuilderByStore $subject,
        $from,
        $store
    ) {
        
        return [$from, $store];
    }
}
