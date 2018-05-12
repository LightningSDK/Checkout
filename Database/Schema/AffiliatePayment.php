<?php

namespace Modules\Checkout\Database\Schema;

use Lightning\Database\Schema;

class AffiliatePayment extends Schema {

    const TABLE = 'checkout_affiliate_payment';

    public function getColumns() {
        return [
            'affiliate_payment_id' => $this->int(true),
            'order_id' => $this->int(true),
            'payment_id' => $this->int(true),
            'user_id' => $this->int(true),
            'affiliate_id' => $this->int(true),
            'amount' => $this->int(),
            'type' => $this->char(2),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'affiliate_payment_id',
        ];
    }
}