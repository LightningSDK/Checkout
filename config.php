<?php

return [
    'package' => [
        'module' => 'Checkout',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'admin/orders' => \Modules\Checkout\Pages\Admin\Orders::class,
            'admin/products' => \Modules\Checkout\Pages\Admin\Products::class,
            'admin/product-classes' => \Modules\Checkout\Pages\Admin\ProductClasses::class,
            'store/embed' => \Modules\Checkout\Pages\ProductWidget::class,
            'api/cart' => \Modules\Checkout\API\Cart::class,
            'store/checkout' => \Modules\Checkout\Pages\Checkout::class,
            'feeds/products' => \Modules\Checkout\Feeds\Products::class,
            'affiliate/mysales' => \Modules\Checkout\Pages\AffiliateSales::class,
            'admin/affiliates' => \Modules\Checkout\Pages\Admin\Affiliates::class,
        ],
        'dynamic' => [
            'store/.*' => \Modules\Checkout\Pages\Product::class,
        ],
    ],
    'markup' => [
        'renderers' => [
            'checkout' => \Modules\Checkout\View\Checkout::class,
            'checkout-product' => \Modules\Checkout\View\Product::class,
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
            'init_view' => [\Modules\Checkout\View\Checkout::class, 'init'],
        ]
    ],
    'sitemap' => [
        'checkout_products' => \Modules\Checkout\Model\Category::class,
        'checkout_categories' => \Modules\Checkout\Model\Product::class,
    ]
    // TODO: add login update session_id => user_id or cart
];
