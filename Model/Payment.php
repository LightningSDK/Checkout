<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;

class Payment extends Object {
    const TABLE = 'checkout_payment';
    const PRIMARY_KEY = 'payment_id';

    protected $__json_encoded_fields = ['details'];
}
