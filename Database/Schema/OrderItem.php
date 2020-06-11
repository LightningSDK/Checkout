<?php

namespace lightningsdk\checkout\Database\Schema;

use lightningsdk\core\Database\Schema;

class OrderItem extends Schema {

    const TABLE = 'checkout_order_item';

    public function getColumns() {
        return [
            'checkout_order_item_id' => $this->autoincrement(),
            'order_id' => $this->int(true),
            'product_id' => $this->int(true),
            'qty' => $this->int(true),
            'shipped' => $this->int(true),
            'options' => $this->text(),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'checkout_order_item_id',
            'index' => [
                'columns' => ['order_id', 'product_id', 'options'],
                'unique' => true,
            ],
        ];
    }
}
