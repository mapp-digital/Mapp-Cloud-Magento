<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2023 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */

namespace MappDigital\Cloud\Api;

use Magento\Sales\Model\Order;

interface EmailRepositoryInterface
{
    const TRANSACTIONAL_EMAIL_ORDER_CANCEL_TEMPLATE_ID = 'sales_email_order_cancel_template';
    const TRANSACTIONAL_EMAIL_ORDER_CANCEL_GUEST_TEMPLATE_ID = 'sales_email_order_cancel_guest_template';

    /**
     * Send email
     *
     * @param Order $order
     * @param string $emailAddress
     * @param array $from
     * @return void
     */
    public function sendEmail(
        Order $order,
        string $emailAddress,
        array $from
    ): void;
}
