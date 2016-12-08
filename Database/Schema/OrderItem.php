<?php

namespace Modules\Checkout\Database\Schema;

use Lightning\Database\Schema;

class OrderItem extends Schema {

    const TABLE = 'checkout_order_item';

    public function getColumns() {
        return [
            'order_id' => $this->int(true),
            'product_id' => $this->int(true),
            'qty' => $this->int(true),
            'options' => $this->text(),
        ];
    }

    public function getKeys() {
        return [
            'index' => [
                'columns' => ['order_id', 'product_id', 'options'],
                'unique' => true,
            ],
        ];
    }
}
