<?php

namespace lightningsdk\checkout\View;

use Exception;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\View\CSS;
use lightningsdk\core\View\HTML;
use lightningsdk\core\View\JS;
use lightningsdk\checkout\Model\Product;

class Checkout {
    public static function init() {
        // Add the startup initialization script.
        JS::startup('lightning.modules.checkout.init();', ['lightningsdk/checkout' => 'Checkout.js']);
        JS::set('modules.checkout.bitcoin', Configuration::get('modules.checkout.bitcoin', false));
        JS::set('modules.checkout.ach', Configuration::get('modules.checkout.ach', false));
        JS::set('modules.checkout.enable_discounts', Configuration::get('modules.checkout.enable_discounts', false));
        JS::set('modules.checkout.image_manager', Configuration::get('modules.checkout.image_manager', false));
        JS::set('modules.checkout.photo_gallery', class_exists('Modules\PhotoGallery\View\Gallery'));

        CSS::add('/css/modules.css');


        // Init the payment handler for the page.
        $payment_handler = Configuration::get('modules.checkout.handler');
        if (!empty($payment_handler)) {
            call_user_func([$payment_handler, 'init']);
        }
    }

    public static function getHandlers() {
        $settings = Configuration::get('modules.checkout.handlers');
        $handlers = [];

        if (is_string($settings)) {
            $handlers[] = self::loadHandler($settings);
        } elseif (is_array($settings)) {
            foreach ($settings as $key => $setting) {
                if (is_string($setting)) {
                    $handler = new $setting();
                }
                elseif (is_array($setting)) {
                    $handler = new $setting['connector']();
                }
                else {
                    unset($handlers[$key]);
                    continue;
                }

                // Make sure it's configured
                if (!$handler->isConfigured()) {
                    unset($handlers[$key]);
                    continue;
                }

                $handlers[$key] = self::loadHandler($setting);
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
     * @throws Exception
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
            $attributes['data-amount'] = $product->price + $product->flat_shipping;
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
        if (!empty($options['add-to-cart'])) {
            $attributes['data-checkout'] = 'add-to-cart';
        }

        if (!empty($options['img'])) {
            $body = '<img src="' . $options['img'] . '" alt="' . $text . '" style="' . ($options['img-style'] ?? '') . '" />';
        } else {
            $body = $text;
        }

        return '<span ' . HTML::implodeAttributes($attributes) . ' >' . $body . '</span>';
    }
}
