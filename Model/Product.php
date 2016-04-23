<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;

class Product extends Object {
    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';
}
