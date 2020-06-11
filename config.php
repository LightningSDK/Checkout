<?php

return [
    'package' => [
        'module' => 'Checkout',
        'version' => '1.0',
    ],
    'routes' => [
        'static' => [
            'admin/orders' => \lightningsdk\checkout\Pages\Admin\Orders::class,
            'admin/products' => \lightningsdk\checkout\Pages\Admin\Products::class,
            'admin/product-classes' => \lightningsdk\checkout\Pages\Admin\ProductClasses::class,
            'store/embed' => \lightningsdk\checkout\Pages\ProductWidget::class,
            'api/cart' => \lightningsdk\checkout\API\Cart::class,
            'store/checkout' => \lightningsdk\checkout\Pages\Checkout::class,
            'feeds/products' => \lightningsdk\checkout\Feeds\Products::class,
            'affiliate/mysales' => \lightningsdk\checkout\Pages\AffiliateSales::class,
            'admin/affiliates' => \lightningsdk\checkout\Pages\Admin\Affiliates::class,
        ],
        'dynamic' => [
            '^store/.*' => \lightningsdk\checkout\Pages\Product::class,
        ],
    ],
    'markup' => [
        'renderers' => [
            'checkout' => \lightningsdk\checkout\View\Checkout::class,
            'checkout-category' => \lightningsdk\checkout\View\Category::class,
            'checkout-product' => \lightningsdk\checkout\View\Product::class,
        ]
    ],
    'compiler' => [
        'js' => [
            // Module Name
            'Checkout' => [
                // Source file => Dest file
                'Checkout.js' => 'Checkout.min.js',
            ]
        ],
        'css' => [
            'Checkout' => [
                'checkout.scss' => 'lightning.css',
            ],
        ],
    ],
    'modules' => [
        'checkout' => [
            'init_view' => [\lightningsdk\checkout\View\Checkout::class, 'init'],
            'buy_now_text' => 'Buy Now',
        ]
    ],
    'sitemap' => [
        'checkout_products' => \lightningsdk\checkout\Model\Category::class,
        'checkout_categories' => \lightningsdk\checkout\Model\Product::class,
    ],
    'jobs' => [
        'checkout-mailer' => [
            'class' => \lightningsdk\checkout\Jobs\Mail::class,
            'schedule' => '*/30 * * * * *',
            'max_threads' => 1,
        ],
        'amazon-upload-products' => [
            'class' => \lightningsdk\checkout\Jobs\AmazonUpload::class,
            'schedule' => '* 3 * * * *',
            'max_threads' => 1,
        ],
    ],
    'menus' => [
        'admin' => [
            'Store' => [
                'children' => [
                    'Orders' => '/admin/orders',
                    'Products' => '/admin/products',
                    'Product Classes' => '/admin/product-classes',
                    'Affiliates' => '/admin/affiliates',
                ],
            ],
        ],
    ],
    // TODO: add login update session_id => user_id or cart
];
