<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class Products extends Table {

    const TABLE = 'checkout_product';
    const PRIMARY_KEY = 'product_id';

    protected $sort = ['product_id' => 'DESC'];
    protected $duplicatable = true;

    protected $preset = [
        'options' => [
            'type' => 'json',
            'unlisted' => true,
        ],
        'category_id' => [
            'type' => 'lookup',
            'lookuptable' => 'checkout_category',
            'lookupkey' => 'category_id',
            'display_column' => 'name',
            'display_name' => 'Category',
            'allow_blank' => true,
        ],
        'shipping_address' => 'checkbox',
        'description' => [
            'type' => 'html',
            'upload' => true,
        ],
        'qty' => [
            'note' => 'Enter -1 to ignore QTY',
        ],
        'active' => [
            'type' => 'checkbox',
            'default' => true,
        ],
        'subscription' => [
            'type' => 'checkbox',
        ]
    ];

    protected $searchable = true;
    protected $search_fields = ['title'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }
}
