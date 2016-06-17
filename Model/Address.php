<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;

/**
 * Class Address
 *   Can be a shipping or billing address.
 *
 * @package Modules\Checkout\Model
 *
 * @parameter integer $id
 *   The primary key alias.
 * @parameter integer $address_id
 *   The primary key.
 * @parameter string $name
 * @parameter string $street
 * @parameter string $street2
 * @parameter string $city
 * @parameter string $state
 * @parameter string $zip
 * @parameter string $country
 */
class Address extends Object {
    const TABLE = 'checkout_address';
    const PRIMARY_KEY = 'address_id';

    /**
     * Get an HTML formatted address block.
     *
     * @return string
     */
    public function getHTMLFormatted() {
        $output = [];
        $output[] = $this->name;
        if (!empty($this->street)) {
            $output[] = $this->street;
        }
        if (!empty($this->street2)) {
            $output[] = $this->street2;
        }
        if (!empty($this->city)) {
            $output[] = $this->city . ', ' . $this->state . ' ' . $this->zip;
        }
        return implode('<br>', $output);
    }
}
