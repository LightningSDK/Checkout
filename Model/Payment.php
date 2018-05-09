<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;

class Payment extends Object {
    const TABLE = 'checkout_payment';
    const PRIMARY_KEY = 'payment_id';

    protected $__json_encoded_fields = ['details'];

    public static function loadByTransactionId($txn_id) {
        return self::loadAll(['gateway_id' => $txn_id]);
    }
}
