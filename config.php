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
            'api/cart' => 'Modules\\Checkout\\API\\Cart',
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
    ]
    // TODO: add login update session_id => user_id or cart
];
