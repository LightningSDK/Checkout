<?php

return [
    'package' => [
        'module' => 'Checkout',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'admin/orders' => 'Modules\\Checkout\\Pages\\Orders',
            'api/cart' => 'Modules\\Checkout\\API\\Cart',
        ]
    ],
    // TODO: add login update session_id => user_id or cart
];
