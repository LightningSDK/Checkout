<?php

namespace lightningsdk\checkout\Database\Schema;

use Lightning\Database\Schema;

class Address extends Schema {

    const TABLE = 'checkout_address';

    public function getColumns() {
        return [
            'address_id' => $this->int(true),
            'name' => $this->varchar(128),
            'address' => $this->varchar(128),
            'address2' => $this->varchar(128),
            'city' => $this->varchar(64),
            'state' => $this->varchar(4),
            'zip' => $this->varchar(16),
            'country' => $this->char(2),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'address_id',
        ];
    }
}