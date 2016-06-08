<?php

return [
    'package' => [
        'module' => 'Checkout',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'admin/orders' => 'Modules\\Checkout\\Pages\\Orders',
        ]
    ],
];
