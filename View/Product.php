<?php

namespace lightningsdk\checkout\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;
use Lightning\View\HTML;
use lightningsdk\checkout\Model\Product as ProductModel;

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
     *
     * Renders a product or list of products with image.
     *
     * TODO: This should call the Checkout::renderMarkup() for rendering the button.
     */
    public static function renderMarkup($options, $vars) {
        Checkout::init();

        $product_ids = explode(',', $options['products']);
        $products = ProductModel::loadMultipleByIds($product_ids);

        $output = '';

        $config = Configuration::get('modules.checkout');

        /** @var \lightningsdk\checkout\Model\Product $product */
        $li_class = $options['ul-class'] ?? 'column column-block';
        foreach ($products as $product) {
            $button_text = (!empty($config['buy_now_text']) && $config['buy_now_text'] == '$price') ? '$' . intval($product->price) : $config['buy_now_text'];
            $output .= '
                <div class="item ' . $li_class . '">
                    <a href="/store/' . $product->url . '">
                        <img src="' . $product->getImage() . '" style="border-radius: 10px;" alt="' . Scrub::toHTML($product->title) . '"><br>
                    </a>
                    <span class="button medium red checkout-product" id="' . $product->sku . '" data-checkout-product-id="' . $product->id . '" data-checkout="add-to-cart">' . $button_text . '</span>
                </div>';
        }

        $form_attributes = [];
        foreach ($options as $key => $val) {
            if (preg_match('/^data-/', $key)) {
                $form_attributes[$key] = $val;
            }
        }

        return '<div class="' . ($options['ul-class'] ?? 'grid-x grid-margin-x grid-margin-y small-up-2 medium-up-3 large-up-4') . '" ' . HTML::implodeAttributes($form_attributes) . '>' . $output . '</div>';
    }
}
