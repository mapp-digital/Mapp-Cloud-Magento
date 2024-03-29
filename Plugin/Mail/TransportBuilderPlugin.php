<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Plugin\Mail;

use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use MappDigital\Cloud\Framework\Mail\Transport;
use Magento\Framework\Mail\Template\FactoryInterface;

class TransportBuilderPlugin
{
    protected array $parameters = [];

    public function __construct(
        protected ConnectHelper $mappConnectHelper,
        protected FactoryInterface $templateFactory
    )
    {
        $this->reset();
    }

    /**
     * @throws LocalizedException
     */
    public function aroundGetTransport(TransportBuilder $subject, \Closure $proceed)
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return $proceed();
        }

        $mappConnectClient = $this->mappConnectHelper->getMappConnectClient();

        if (!$mappConnectClient) {
            return $proceed();
        }

        if ($this->mappConnectHelper->getConfigValue('export', 'newsletter_enable')
            && in_array($this->parameters['identifier'], [
                "newsletter_subscription_confirm_email_template",
                "newsletter_subscription_success_email_template",
                "newsletter_subscription_un_email_template"
            ])) {
            $result = new Transport($mappConnectClient, 0, []);
            $this->reset();
            return $result;
        }

        if ($messageId = $this->mappConnectHelper->templateIdToConfig($this->parameters['identifier'])) {
            if ($this->mappConnectHelper->getConfigValue('export', 'transaction_enable')
                && in_array($this->parameters['identifier'], [
                    "sales_email_order_template",
                    "sales_email_order_guest_template"
                ])) {
                $messageId = 0;
            }

            $template = $this->templateFactory->get($this->parameters['identifier'], $this->parameters['model'])
                ->setVars($this->parameters['vars'])
                ->setOptions($this->parameters['options']);

            $template->processTemplate();
            $filer = $template->getTemplateFilter();

            $params = $this->parameters;
            $params['params'] = [];

            foreach ($template->getVariablesOptionArray() as $v) {
                $label = 'param_' . strtolower($v['label']->render());
                $label = preg_replace('/[^a-z0-9]+/', '_', $label);
                $label = preg_replace('/_+$/', '', $label);
                $params['params'][$label] = $filer->filter($v['value']);
            }

            $result = new Transport($mappConnectClient, $messageId, $params);
            $this->reset();

            return $result;
        }

        $returnValue = $proceed();
        $this->reset();
        return $returnValue;
    }

    public function beforeAddTo(TransportBuilder $subject, $address, $name = '')
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return;
        }

        $this->parameters['to'][] = $address;
        return null;
    }

    public function beforeSetTemplateOptions(TransportBuilder $subject, $templateOptions)
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return;
        }

        $this->parameters['options'] = $templateOptions;
        return null;
    }

    public function beforeSetTemplateIdentifier(TransportBuilder $subject, $templateIdentifier)
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return;
        }

        $this->parameters['identifier'] = $templateIdentifier;
        return null;
    }

    public function beforeSetTemplateModel(TransportBuilder $subject, $templateModel)
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return;
        }

        $this->parameters['model'] = $templateModel;
        return null;
    }

    public function beforeSetTemplateVars(TransportBuilder $subject, $templateVars)
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return;
        }

        $this->parameters['vars'] = $templateVars;
        return null;
    }

    protected function reset()
    {
        if (!$this->mappConnectHelper->isMappEmailEnabled()) {
            return;
        }

        $this->parameters = [
            'options' => null,
            'identifier' => null,
            'model' => null,
            'vars' => null,
            'to' => []
        ];
    }
}
