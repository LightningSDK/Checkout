<?php

namespace Modules\Checkout\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;
use Lightning\View\HTML;
use Modules\Checkout\Model\Product as ProductModel;

class Product {

    /**
     * @param $options
     *   string text - The text to display in the the button. Default: 'Buy Now'
     *   integer product-id - The product ID to add to the cart on checkout
     *   boolean create-customer - Whether to save the user's card information for rebilling. Default: false.
     *   string redirect - A redirection URL after the purchase is complete.
     *   string class - A string of classes to be added to the button. Default: 'button red'.
     *
     * @param $vars
     * @return string
     */
    public static function renderMarkup($options, $vars) {
        Checkout::init();

        $product_ids = explode(',', $options['products']);
        $products = ProductModel::loadMultipleByIds($product_ids);

        $output = '';

        $config = Configuration::get('modules.checkout');

        /** @var \Modules\Checkout\Model\Product $product */
        foreach ($products as $product) {
            $button_text = (!empty($config['buy_now_text']) && $config['buy_now_text'] == '$price') ? '$' . intval($product->price) : $config['buy_now_text'];
            $output .= '
                <li class="item">
                    <a href="/store/' . $product->url . '">
                        <img src="' . $product->getImage() . '" style="border-radius: 10px;" alt="' . Scrub::toHTML($product->title) . '"><br>
                    </a>
                    <span class="button medium red checkout-product" id="' . $product->sku . '" data-checkout-product-id="' . $product->id . '" data-checkout="add-to-cart">' . $button_text . '</span>
                </li>';
        }

        $form_attributes = [];
        foreach ($options as $key => $val) {
            if (preg_match('/^data-/', $key)) {
                $form_attributes[$key] = $val;
            }
        }

        return '<ul class="' . ($options['ul-class'] ?? '') . '" ' . HTML::implodeAttributes($form_attributes) . '>' . $output . '</ul>';
    }
}
