<?php

return [
    'package' => [
        'module' => 'Stripe',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'admin/orders' => 'Modules\\Checkout\\Pages\\Orders',
        ]
    ],
];
