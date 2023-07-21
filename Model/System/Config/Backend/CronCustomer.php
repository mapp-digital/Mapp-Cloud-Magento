<?php

namespace MappDigital\Cloud\Model\System\Config\Backend;

class CronCustomer extends Cron
{
    const CRON_STRING_PATH = 'crontab/default/jobs/mapp_export_customer/schedule/cron_expr';
    const CRON_MODEL_PATH = 'crontab/default/jobs/mapp_export_customer/run/model';
}
