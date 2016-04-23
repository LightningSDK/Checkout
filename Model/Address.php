<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;

class Address extends Object {
    const TABLE = 'checkout_address';
    const PRIMARY_KEY = 'address_id';
}
