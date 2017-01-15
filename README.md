# Lightning-Checkout: Integrated Shopping Cart for Lightning

# What it does

Multi-item shopping cart
Multiple options and conditional options for items.
Does not include payment processing, that is done through integrated modules:
    Lightning-Stripe
Available order fulfillment modules:
    Lightning-GoShippo
    Lightning-ThePrintful

# Installation and Configuration
```
$conf = [
    'modules' => [
        'checkout' => [
            // The payment handler class.
            // This may be added by the payment handler's own module.
            // In the future, this will be an array or a string that
            // can accept multiple payment handlers.
            'handler' => 'Path\\To\\Payment\\Handler',
            
            // Allow an option to enter discount codes in the
            // shopping cart view.
            'enable_discounts' => false,
        ],
    ]
];
```
