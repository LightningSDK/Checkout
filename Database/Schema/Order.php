<?php

namespace lightningsdk\checkout\Database\Schema;

use Lightning\Database\Schema;

class Order extends Schema {

    const TABLE = 'checkout_order';

    public function getColumns() {
        return [
            'order_id' => $this->int(true),
            'user_id' => $this->int(true),
            'session_id' => $this->int(true),
            'status' => $this->int(true),
            'time' => $this->int(true),
            'paid' => $this->int(true),
            'shipped' => $this->int(true),
            'shipping_address' => $this->int(true),
            'tax' => $this->int(),
            'shipping' => $this->int(),
            'total' => $this->int(),
            'locked' => $this->int(true, self::TINYINT),
            'details' => $this->text(),
            'gateway_id' => $this->varchar(128),
            'discounts' => $this->varchar(255),
            'referrer' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'order_id',
        ];
    }
}
