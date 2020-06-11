<?php

namespace lightningsdk\checkout\Database\Schema;

use Lightning\Database\Schema;

class Product extends Schema {

    const TABLE = 'checkout_product';

    public function getColumns() {
        return [
            'product_id' => $this->int(true),
            'category_id' => $this->int(true),
            'price' => $this->decimal(true, 10, 2),
            'flat_shipping' => $this->decimal(true, 10, 2),
            'flat_shipping_more' => $this->decimal(true, 10, 2),
            'shipping_address' => $this->int(true),
            'options' => $this->text(),
            'description' => $this->text(),
            'title' => $this->varchar(128),
            'sku' => $this->varchar(45),
            'url' => $this->varchar(64),
            'qty' => $this->int(),
            'active' => $this->int(true, Schema::TINYINT),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'product_id',
            'url' => [
                'columns' => 'url',
                'unique' => true,
            ],
        ];
    }
}
