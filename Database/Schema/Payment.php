<?php

namespace Modules\Checkout\Database\Schema;

use Lightning\Database\Schema;

class Payment extends Schema {

    const TABLE = 'checkout_payment';

    public function getColumns() {
        return [
            'payment_id' => $this->int(true),
            'order_id' => $this->int(true),
            'amount' => $this->int(),
            'time' => $this->int(true),
            'status' => $this->int(true),
            'billing_address' => $this->int(true),
            'details' => $this->text(),
            'currency' => $this->varchar(4),
            'method' => $this->varchar(16),
            'gateway_id' => $this->varchar(128),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'payment_id',
            'order_id' => [
                'columns' => ['order_id'],
            ]
        ];
    }
}
