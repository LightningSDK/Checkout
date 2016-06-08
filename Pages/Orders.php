<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Table;
use Overridable\Lightning\Tools\ClientUser;

class Orders extends Table {
    const TABLE = 'checkout_order';
    const PRIMARY_KEY = 'order_id';

    protected $field_order = ['order_id', 'status', 'shipping_address'];
    protected $sort = ['order_id' => 'DESC'];

    protected $preset = [
        'time' => 'datetime',
    ];

    protected $joins = [
        [
            'left_join' => 'checkout_address',
            'on' => ['address_id' => ['expression' => 'shipping_address']],
        ]
    ];
    protected $joinFields = ['checkout_address.*'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function initSettings() {
        parent::initSettings();
        $this->preset['shipping_address'] = [
            'render_list_field' => function(&$row) {
                return $row['name'] . '<br>' . $row['street'] . ' ' . $row['street2'] . '<br>' . $row['city'] . ', ' . $row['state'] . ' ' . $row['zip'];
            }
        ];
    }
}
