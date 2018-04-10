<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Configuration;
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

    public static function getSitemapUrls() {
        $urls = [];

        // Load the pages.
        $web_root = Configuration::get('web_root');
        $categories = static::loadAll();

        foreach($categories as $c) {
            $urls[] = [
                'loc' => $web_root . "/store/{$c->url}",
                'lastmod' => date('Y-m-d', time()),
                'changefreq' => 'monthly',
                'priority' => 90 / 100,
            ];
        }

        return $urls;
    }

    public function getBreadcrumbs($isFinal = true) {
        $breadcrumbs = [];
        if ($isFinal) {
            $breadcrumbs['#current'] = $this->name;
        } else {
            $breadcrumbs[$this->url] = $this->name;
        }

        $cat = $this;
        while ($cat = $cat->getParentCategory()) {
            $breadcrumbs = [$cat->url => $cat->name] + $breadcrumbs;
        }

        return $breadcrumbs;
    }

    /**
     * @return Category
     */
    public function getParentCategory() {
        if (!empty($this->parent_id)) {
            return Category::loadByID($this->parent_id);
        }
        return null;
    }
}
