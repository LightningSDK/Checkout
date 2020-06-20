<?php

namespace lightningsdk\checkout\Model;

use lightningsdk\core\Model\BaseObject;

/**
 * Class Address
 *   Can be a shipping or billing address.
 *
 * @package lightningsdk\checkout\Model
 *
 * @parameter integer $id
 *   The primary key alias.
 * @parameter integer $order_id
 * @parameter integer $payment_id
 * @parameter integer $user_id
 * @parameter integer $affiliate_id
 * @parameter integer $amount
 * @parameter string $type
 */
class AffiliatePaymentCore extends BaseObject {
    const TABLE = 'checkout_affiliate_payment';
    const PRIMARY_KEY = 'affiliate_payment_id';
}
