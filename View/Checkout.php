<?php

namespace Modules\Checkout\View;

use Lightning\Tools\Configuration;
use Lightning\View\JS;

class Checkout {
    public static function init() {
        // Add the startup initialization script.
        JS::startup('lightning.modules.checkout.init();', ['/js/checkout.min.js']);

        // Init the payment handler for the page.
        $payment_handler = Configuration::get('modules.checkout.handler');
        if (!empty($payment_handler)) {
            call_user_func($payment_handler . '::init');
        }
    }
}
