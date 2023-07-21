<?php

namespace MappDigital\Cloud\Model\System\Config\Backend;

class CronProduct extends Cron
{
    const CRON_STRING_PATH = 'crontab/default/jobs/mapp_export_product/schedule/cron_expr';
    const CRON_MODEL_PATH = 'crontab/default/jobs/mapp_export_product/run/model';
}
