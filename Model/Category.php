<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;

class Category extends Object {
    const TABLE = 'checkout_category';
    const PRIMARY_KEY = 'category_id';

    public static function loadByURL($url) {
        $data = Database::getInstance()->selectRow(self::TABLE, ['url' => ['LIKE', $url]]);
        if (!empty($data)) {
            return new static($data);
        } else {
            return null;
        }
    }
}
