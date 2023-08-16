<?php

namespace MappDigital\Cloud\Model\System\Config\Backend;

class CronOrder extends Cron
{
    const CRON_STRING_PATH = 'crontab/default/jobs/mapp_export_order/schedule/cron_expr';
    const CRON_MODEL_PATH = 'crontab/default/jobs/mapp_export_order/run/model';
}
