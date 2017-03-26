<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class Products extends Table {

    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';

    protected $preset = [
        'options' => [
            'type' => 'json',
            'unlisted' => true,
        ],
        'shipping_address' => 'checkbox',
        'qty' => [
            'note' => 'Enter -1 to ignore QTY',
        ],
        'active' => [
            'type' => 'checkbox',
            'default' => true,
        ]
    ];

    protected $searchable = true;
    protected $search_fields = ['title'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }
}
