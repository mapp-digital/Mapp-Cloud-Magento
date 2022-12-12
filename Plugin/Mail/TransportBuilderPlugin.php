<?php

namespace MappDigital\Cloud\Plugin\Mail;

use MappDigital\Cloud\Helper\Data;
use Magento\Framework\Mail\Template\TransportBuilder;
use MappDigital\Cloud\Framework\Mail\Transport;
use Magento\Framework\Mail\Template\FactoryInterface;

class TransportBuilderPlugin
{

    protected $dataHelper;

    protected $parameters;

    protected $templateFactory;

    protected $_config;

    public function __construct(
        Data $dataHelper,
        FactoryInterface $templateFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->templateFactory = $templateFactory;
        $this->reset();
    }

    public function aroundGetTransport(TransportBuilder $subject, \Closure $proceed)
    {
        if ($mappconnect = $this->dataHelper->getMappConnectClient()) {

            if ($this->dataHelper->getConfigValue('export', 'newsletter_enable')
            && in_array($this->parameters['identifier'], [
              "newsletter_subscription_confirm_email_template",
              "newsletter_subscription_success_email_template",
              "newsletter_subscription_un_email_template"
            ])) {
                $result = new Transport($mappconnect, 0, []);
                $this->reset();
                return $result;
            }

            if ($messageId = $this->dataHelper->templateIdToConfig($this->parameters['identifier'])) {

                if ($this->dataHelper->getConfigValue('export', 'transaction_enable')
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
                    $label = 'param_'.strtolower($v['label']->render());
                    $label = preg_replace('/[^a-z0-9]+/', '_', $label);
                    $label = preg_replace('/_+$/', '', $label);
                    $params['params'][$label] = $filer->filter($v['value']);
                }

                $result = new Transport($mappconnect, $messageId, $params);
                $this->reset();
                return $result;

            }
        }
        $returnValue = $proceed();
        $this->reset();
        return $returnValue;
    }

    public function beforeAddTo(TransportBuilder $subject, $address, $name = '')
    {
        $this->parameters['to'][] = $address;
        return null;
    }

    public function beforeSetTemplateOptions(TransportBuilder $subject, $templateOptions)
    {
        $this->parameters['options'] = $templateOptions;
        return null;
    }

    public function beforeSetTemplateIdentifier(TransportBuilder $subject, $templateIdentifier)
    {
        $this->parameters['identifier'] = $templateIdentifier;
        return null;
    }

    public function beforeSetTemplateModel(TransportBuilder $subject, $templateModel)
    {
        $this->parameters['model'] = $templateModel;
        return null;
    }

    public function beforeSetTemplateVars(TransportBuilder $subject, $templateVars)
    {
        $this->parameters['vars'] = $templateVars;
        return null;
    }

    protected function reset()
    {
        $this->parameters = [
          'options' => null,
          'identifier' => null,
          'model' => null,
          'vars' => null,
          'to' => []
        ];
    }
}
