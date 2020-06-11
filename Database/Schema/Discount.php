<?php

namespace lightningsdk\checkout\Database\Schema;

use Lightning\Database\Schema;

class Discount extends Schema {

    const TABLE = 'checkout_discount';

    public function getColumns() {
        return [
            'discount_id' => $this->int(true),
            'code' => $this->varchar(32),
            'discounts' => $this->text(),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'discount_id',
            'code' => [
                'columns' => 'code',
                'unique' => true,
            ],
        ];
    }
}
