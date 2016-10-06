<?php

namespace Modules\Checkout\Renderers;

use DOMElement;
use Lightning\View\HTML;

class Checkout {
    public static function render(DOMElement $element, $vars) {
        \Modules\Checkout\View\Checkout::init();

        $attributes = [];
        $attributes['class'] = 'checkout-product ' . ($element->getAttribute('class') ?: 'button red');
        $text = $element->getAttribute('text') ?: 'Buy Now';
        if ($product_id = $element->getAttribute('product-id')) {
            $attributes['data-checkout-product-id'] = $product_id;
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
