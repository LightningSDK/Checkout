<?php

namespace lightningsdk\checkout\Database\Schema;

use Lightning\Database\Schema;

class ProductClass extends Schema {

    const TABLE = 'checkout_product_class';

    public function getColumns() {
        return [
            'product_class_id' => $this->int(true),
            'name' => $this->varchar(45),
            'options' => $this->text(),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'product_class_id',
        ];
    }
}
