<?php

return [
    'package' => [
        'module' => 'Checkout',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'admin/orders' => 'Modules\\Checkout\\Pages\\Orders',
            'admin/products' => 'Modules\\Checkout\\Pages\\Products',
            'store/embed' => 'Modules\\Checkout\\Pages\\ProductWidget',
            'api/cart' => 'Modules\\Checkout\\API\\Cart',
            'store/checkout' => 'Modules\\Checkout\\Pages\\Checkout',
            'feeds/products' => 'Modules\\Checkout\\Feeds\\Products',
        ],
        'dynamic' => [
            'store/.*' => 'Modules\\Checkout\\Pages\\Product',
        ],
    ],
    'markup' => [
        'renderers' => [
            'checkout' => 'Modules\\Checkout\\View\\Checkout'
        ]
    ],
    'js' => [
        // Module Name
        'Checkout' => [
            // Source file => Dest file
            'Checkout.js' => 'Checkout.min.js',
        ]
    ],
    'modules' => [
        'checkout' => [
            'init_view' => 'Modules\\Checkout\\View\\Checkout::init',
        ]
    ],
    'sitemap' => [
        'checkout_products' => 'Modules\\Checkout\\Model\\Category',
        'checkout_categories' => 'Modules\\Checkout\\Model\\Product',
    ]
    // TODO: add login update session_id => user_id or cart
];
