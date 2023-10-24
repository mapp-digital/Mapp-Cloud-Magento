<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Api;

interface EmailRepositoryInterface
{
    const TRANSACTIONAL_EMAIL_TEMPLATE_ID = 'sales_email_order_cancel_template';

    /**
     * Send email
     *
     * @param array $templateVars
     * @param string $emailAddress
     * @param array $from
     * @return void
     */
    public function sendEmail(
        array $templateVars,
        string $emailAddress,
        array $from
    );
}
