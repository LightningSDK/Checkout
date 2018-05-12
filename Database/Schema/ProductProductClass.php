<?php

namespace Modules\Checkout\Database\Schema;

use Lightning\Database\Schema;

class ProductProductClass extends Schema {

    const TABLE = 'checkout_product';

    public function getColumns() {
        return [
            'product_id' => $this->int(true),
            'product_class_id' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'product_id',
            'index1' => [
                'columns' => ['product_id', 'product_class_id'],
                'unique' => true,
            ],
        ];
    }
}
