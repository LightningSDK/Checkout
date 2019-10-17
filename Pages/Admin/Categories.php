<?php

namespace Modules\Checkout\Pages\Admin;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Request;

class Categories extends Table {

    const TABLE = 'checkout_category';
    const PRIMARY_KEY = 'category_id';

    protected $sort = ['name' => 'ASC'];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    protected function initSettings() {
        parent::initSettings();
        $this->preset['url']['submit_function'] = function(&$output) {
            $output['url'] = Request::post('url', Request::TYPE_URL) ?: Request::post('title', Request::TYPE_URL);
        };
    }
}
