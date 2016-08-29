<?php

namespace Modules\Checkout\Pages;

use Lightning\Pages\Page;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\CSS;
use Lightning\View\JS;
use Modules\Checkout\Model\Category;
use Modules\Checkout\Model\Product as ProductModel;

class Product extends Page {

    protected $page = ['product', 'Checkout'];

    public function get() {
        CSS::add('/css/modules.css');
        $content_locator = Request::getFromURL('/store\/(.*)/');

        if (empty($content_locator)) {
            Output::notFound();
        }

        $template = Template::getInstance();
        if ($product = ProductModel::loadByURL($content_locator)) {
            // Setup the templates.
            $template->set('product', $product);

            if (!empty($product->options->options_popup_template)) {
                $template->set('fields_template', $product->options->options_popup_template);
            } else {
                $template->set('fields_template', ['default_options_layout', 'Checkout']);
            }

            // Init the checkout methods
            JS::startup('lightning.modules.checkout.init();lightning.modules.checkout.initProductOptions(' . json_encode(['options' => $product->options, 'base_price' => $product->price]) . ');', '/js/checkout.min.js');

            // Init the payment handler.
            $payment_handler = Configuration::get('modules.checkout.handler');
            if (!empty($payment_handler)) {
                call_user_func($payment_handler . '::init');
            }
        } elseif ($category = Category::loadByURL($content_locator)) {
            $template->set('category', $category);
            // TODO: Add pagination
            $this->page[0] = 'category';
            $template->set('products', ProductModel::loadAll(['category_id' => $category->id]));
        } else {
            Output::notFound();
        }
    }
}
