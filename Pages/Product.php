<?php

namespace lightningsdk\checkout\Pages;

use lightningsdk\core\Pages\Page;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\JS;
use lightningsdk\checkout\Model\Category;
use lightningsdk\checkout\Model\Product as ProductModel;
use lightningsdk\checkout\View\Checkout;

class Product extends Page {

    protected $page = ['product_wrapper', 'lightningsdk/checkout'];

    public function get() {
        $content_locator = Request::getFromURL('/store\/(.*)/');

        if (empty($content_locator)) {
            Output::notFound();
        }

        $template = Template::getInstance();

        if ($product = ProductModel::loadByURL($content_locator)) {
            // If this is a product page.
            $template->set('product', $product);

            if (!empty($product->options['options_popup_template'])) {
                $template->set('fields_template', $product->options['options_popup_template']);
            }

            if (!empty($product->options['product_template'])) {
                $template->set('product_template', $product->options['product_template']);
            } else {
                $template->set('product_template', ['product', 'lightningsdk/checkout']);
            }

            // Init the checkout methods
            Checkout::init();
            JS::startup('lightning.modules.checkout.initProductOptions(' . json_encode(['options' => $product->options, 'base_price' => $product->price]) . ');', ['lightningsdk/checkout' => 'Checkout.js']);

            // Set up the meta data.
            $this->setMeta('title', $product->title);
            $this->setMeta('description', $product->description);
            $this->setMeta('image', $product->getImage(ProductModel::IMAGE_OG));
            $this->setMeta('keywords', $product->keywords);

            $template->set('breadcrumbs', $product->getBreadcrumbs());

        } elseif ($category = Category::loadByURL($content_locator)) {
            Checkout::init();

            $this->rightColumn = false;
            // If this is a category page.
            $template->set('category', $category);
            // TODO: Add pagination
            $this->page[0] = 'category';
            $products = ProductModel::loadAll([
                'category_id' => $category->id,
                'active' => 1,
            ]);
            $template->set('products', $products);

            $categories = Category::loadAll([
                'parent_id' => $category->id,
            ]);
            $template->set('categories', $categories);

            // Add meta data
            $this->setMeta('title', !empty($category->header_text) ? $category->header_text : $category->name);
            $this->setMeta('description', $category->description);
            foreach ($products as $product) {
                if ($image = $product->getImage(ProductModel::IMAGE_OG)) {
                    $this->setMeta('image', $image);
                    break;
                }
            }
            $this->setMeta('keywords', $category->keywords);

            // Setup breadcrumbs:
            $template->set('breadcrumbs', $category->getBreadcrumbs());
        } else {
            Output::notFound();
        }

        // Attempt to load gallery if available.
        if (class_exists('lightningsdk\photogallery\View\Gallery')) {
            \lightningsdk\photogallery\View\Gallery::init();
            $gallery = new \lightningsdk\photogallery\Model\Gallery();
            $template->set('gallery', $gallery);
        }
    }
}
