<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;

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

    protected $links = [
        'product_classes' => [
            'display_name' => 'Product Classes',
            'key' => 'product_class_id',
            'table' => 'checkout_product_class',
            'index' => 'checkout_product_product_class',
            'display_column' => 'name',
            'list' => 'compact',
        ],
    ];

    protected $searchable = true;
    protected $search_fields = ['title'];
    protected $searchWildcard = Database::WILDCARD_EITHER;

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }
}
