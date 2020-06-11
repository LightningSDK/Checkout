<?php

namespace lightningsdk\checkout\Pages\Admin;

use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Request;

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
