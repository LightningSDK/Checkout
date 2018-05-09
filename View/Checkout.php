<?php

namespace Modules\Checkout\View;

use Exception;
use Lightning\Tools\Configuration;
use Lightning\View\CSS;
use Lightning\View\HTML;
use Lightning\View\JS;
use Modules\Checkout\Model\Product;

class Checkout {
    public static function init() {
        // Add the startup initialization script.
        JS::startup('lightning.modules.checkout.init();', ['Checkout' => 'Checkout.js']);
        JS::set('modules.checkout.bitcoin', Configuration::get('modules.checkout.bitcoin', false));
        JS::set('modules.checkout.ach', Configuration::get('modules.checkout.ach', false));
        JS::set('modules.checkout.enable_discounts', Configuration::get('modules.checkout.enable_discounts', false));

        CSS::add('/css/modules.css');


        // Init the payment handler for the page.
        $payment_handler = Configuration::get('modules.checkout.handler');
        if (!empty($payment_handler)) {
            call_user_func($payment_handler . '::init');
        }
    }

    public static function getHandlers() {
        $settings = Configuration::get('modules.checkout.handlers');
        $handlers = [];

        if (is_string($settings)) {
            $handlers[] = self::loadHandler($settings);
        } elseif (is_array($settings)) {
            foreach ($settings as $setting) {
                $handlers[] = self::loadHandler($setting);
            }
        }

        return $handlers;
    }

    public static function getHandler($id = null) {
        $settings = Configuration::get('modules.checkout.handlers');
        if (is_string($settings) && $id === null) {
            return self::loadHandler($settings);
        } else if (!empty($settings[$id])) {
            return self::loadHandler($settings[$id]);
        }
        throw new Exception('Payment handler not found');
    }

    protected static function loadHandler($setting) {
        if (is_string($setting)) {
            return new $setting();
        } elseif (!empty($setting['connector'])) {
            return new $setting['connector']();
        }
    }

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
        static::init();

        $attributes = [];
        $attributes['class'] = 'checkout-product ' . (!empty($options['class']) ? $options['class'] : 'button red');
        $text = !empty($options['text']) ? $options['text'] : 'Buy Now';
        if (!empty($options['product-id'])) {
            $product_id = $options['product-id'];
            $attributes['data-checkout-product-id'] = $product_id;
            $product = Product::loadByID($product_id);
            $attributes['data-title'] = $product->title;
            $attributes['data-amount'] = $product->price;
        }
        if (!empty($options['create-customer'])) {
            if ($options['create-customer'] == 'true') {
                $attributes['data-create-customer'] = 'true';
            }
        }
        if (!empty($options['redirect'])) {
            $attributes['data-redirect'] = $options['redirect'];
        }
        if (!empty($options['shipping-address']) && $options['shipping-address'] == "true") {
            $attributes['data-shipping-address'] = $options['shipping-address'];
        }
        if (!empty($options['bitcoin']) && $options['bitcoin'] == "true") {
            $attributes['data-bitcoin'] = $options['bitcoin'];
        }

        return '<span ' . HTML::implodeAttributes($attributes) . ' >' . $text . '</span>';
    }
}
