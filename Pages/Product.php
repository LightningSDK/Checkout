<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Page;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Template;

class Product extends Page {

    protected $page = ['product', 'Checkout'];

    public function get() {
        $content_locator = Request::getFromURL('/store\/(.*)/');
        if (empty($content_locator)) {
            Output::notFound();
        }

        $product = \Modules\Checkout\Model\Product::loadByURL($content_locator);
        Template::getInstance()->set('product', $product);
    }
}
