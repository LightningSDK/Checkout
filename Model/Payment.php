<?php

namespace lightningsdk\checkout\Model;

use Lightning\Model\BaseObject;

class Payment extends BaseObject {
    const TABLE = 'checkout_payment';
    const PRIMARY_KEY = 'payment_id';

    protected $__json_encoded_fields = ['details'];

    public static function loadByTransactionId($txn_id) {
        return self::loadAll(['gateway_id' => $txn_id]);
    }
}
