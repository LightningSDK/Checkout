<?php

namespace lightningsdk\checkout\Feeds;

use Lightning\Model\URL;
use Lightning\View\Feed;
use lightningsdk\checkout\Model\Product;

class Products extends Feed {

    const NAME = 'products';

    public function hasAccess() {
        return true;
    }

    public function load() {
        $this->cursor = Product::loadAll(['active' => 1]);
    }

    public function getCSVHeaders() {
        return [
            'Title',
            'SKU',
            'Category',
            'Image URL',
        ];
    }

    /**
     * @param Product $product
     *
     * @return array
     */
    public function next($product) {
        return [
            'title' => $product->title,
            'SKU' => $product->sku,
            'category' => $product->getCategory()->name,
            'image_url' => URL::getAbsolute($product->getImage()),
        ];
    }
}
