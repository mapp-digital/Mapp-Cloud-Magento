<?php

namespace MappDigital\Cloud\Model\Config\Source;

class CronFrequencyTypes
{
    const CRON_HOURLY = 'H';
    const CRON_DAILY = 'D';

    /**
     * Return array of cron frequency types
     *
     * @return array
     */
    public function getCronFrequencyTypes()
    {
        return [
            self::CRON_HOURLY => __('Hourly'),
            self::CRON_DAILY => __('Daily')
        ];
    }
}
