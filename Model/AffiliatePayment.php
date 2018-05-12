<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;

/**
 * Class Address
 *   Can be a shipping or billing address.
 *
 * @package Modules\Checkout\Model
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
class AffiliatePayment extends Object {
    const TABLE = 'checkout_affiliate_payment';
    const PRIMARY_KEY = 'affiliate_payment_id';
}
