<?php

namespace Modules\Checkout\Renderers;

use DOMElement;
use Lightning\View\HTML;
use Modules\Checkout\Model\Product;

class Checkout {
    public static function render(DOMElement $element, $vars) {
        \Modules\Checkout\View\Checkout::init();

        $attributes = [];
        $attributes['class'] = 'checkout-product ' . ($element->getAttribute('class') ?: 'button red');
        $text = $element->getAttribute('text') ?: 'Buy Now';
        if ($product_id = $element->getAttribute('product-id')) {
            $attributes['data-checkout-product-id'] = $product_id;
            $product = Product::loadByID($product_id);
            $attributes['data-title'] = $product->title;
            $attributes['data-amount'] = $product->price;
        }
        if ($save_user = $element->getAttribute('create-customer')) {
            if ($save_user == 'true') {
                $attributes['data-create-customer'] = 'true';
            }
        }
        if ($redirect = $element->getAttribute('redirect')) {
            $attributes['data-redirect'] = $redirect;
        }

        return '<span ' . HTML::implodeAttributes($attributes) . ' >' . $text . '</span>';
    }
}
