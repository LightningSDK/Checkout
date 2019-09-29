<?php

namespace Modules\Checkout\Model;

use Lightning\Model\Object;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Image;

class CategoryOverridable extends Object {
    const TABLE = 'checkout_category';
    const PRIMARY_KEY = 'category_id';

    const IMAGE_LISTING = 'listing-image';
    const IMAGE_OG = 'og-image';
    const IMAGE_MAIN = 'image';

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
     *
     * @throws \Exception
     */
    public function getParentCategory() {
        if (!empty($this->parent_id)) {
            return Category::loadByID($this->parent_id);
        }
        return null;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getImage($type = self::IMAGE_LISTING) {
        $image = $this->image;
        // If image manager is installed, use it.
        if (class_exists('Modules\ImageManager\Model\Image')) {
            $size = 1000;
            if ($type == self::IMAGE_LISTING) {
                $size = 250;
            }
            $image = \Modules\ImageManager\Model\Image::getImage($image, $size, Image::FORMAT_JPG);
        }

        return $image;
    }
}
