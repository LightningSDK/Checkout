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
}
