<?php

namespace MappDigital\Cloud\Model\System\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MappDigital\Cloud\Model\Config\Source\CronFrequencyTypes;

abstract class Cron extends Value
{
    const CRON_STRING_PATH = '';
    const CRON_MODEL_PATH = '';

    protected string $runModelPath = '';
    protected ValueFactory $valueFactory;

    public function __construct(
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        ValueFactory         $valueFactory,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null,
                             $runModelPath = '',
        array                $data = []
    )
    {
        $this->runModelPath = $runModelPath;
        $this->valueFactory = $valueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Cron settings after save
     *
     * @return Cron
     * @throws LocalizedException
     */
    public function afterSave()
    {
        $cronExprString = '';

        if (!$this->getFieldsetDataValue('enable_export')) {
            return parent::afterSave();
        }

        $hourly = CronFrequencyTypes::CRON_HOURLY;
        $daily = CronFrequencyTypes::CRON_DAILY;

        $frequency = $this->getFieldsetDataValue('frequency');

        if ($frequency == $hourly) {
            $minutes = (int)$this->getFieldsetDataValue('minutes');
            if ($minutes >= 0 && $minutes <= 59) {
                $cronExprString = "{$minutes} * * * *";
            } else {
                throw new LocalizedException(
                    __('The valid number of minutes needs to be entered. Enter and try again.')
                );
            }
        } elseif ($frequency == $daily) {
            $time = $this->getFieldsetDataValue('time');
            $timeMinutes = intval($time[1]);
            $timeHours = intval($time[0]);
            $cronExprString = "{$timeMinutes} {$timeHours} * * *";
        }

        try {
            $this->valueFactory->create()->load(
                static::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                static::CRON_STRING_PATH
            )->save();

            $this->valueFactory->create()->load(
                static::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->runModelPath
            )->setPath(
                static::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__('The Cron expression was unable to be saved.'));
        }

        return parent::afterSave();
    }
}
