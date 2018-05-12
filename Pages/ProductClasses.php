<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class ProductClasses extends Table {

    const TABLE = 'checkout_product_class';
    const PRIMARY_KEY = 'product_class_id';

    protected $sort = ['name' => 'ASC'];
    protected $duplicatable = true;

    protected $preset = [
        'name' => 'string',
        'options' => [
            'type' => 'json',
            'unlisted' => true,
        ],
    ];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }
}
