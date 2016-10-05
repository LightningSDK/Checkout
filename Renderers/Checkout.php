<?php

namespace Modules\Checkout\Renderers;

use DOMElement;

class Checkout {
    public static function render(DOMElement $element, $vars) {
        \Modules\Checkout\View\Checkout::init();

        $class = $element->getAttribute('class') ?: 'button red';
        $text = $element->getAttribute('text') ?: 'Buy Now';
        $other_html = '';
        if ($product_id = $element->getAttribute('product-id')) {
            $other_html .= ' data-checkout-product-id="' . $product_id . '"';
        }
        if ($save_user = $element->getAttribute('create-customer')) {
            if ($save_user == 'true') {
                $other_html .= 'data-create-customer="true"';
            }
        }

        return '<span class="checkout-product ' . $class . '" ' . $other_html . ' >' . $text . '</span>';
    }
}
