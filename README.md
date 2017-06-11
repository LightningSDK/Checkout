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

# Adding to a page

You can add checkout buttons simply by adding markup to a span element. The `checkout-product` class must be present on all elements to activate the click listener. All other options are specified via data attributes.

```
<span class="checkout-product" data-checkout-product-id="{product id}" data-checkout="add-to-cart">Buy Now</span>
```

The following fields are available:

* data-checkout-product-id
   Mandatory: the id of the product to buy. This can be a single purchase or a subscription.
   
* data-checkout
   Optional
   Values:
     `buy-now` (default) - Takes the user directly to payment
     `add-to-cart` - Adds to a shopping cart displayed in a modal, so the user can continue shopping.
     
* data-create-customer
   Optional
   Values:
     `false` (default)
     `true` - Whather to save the user's card information for future billing
     
* data-redirect
* data-title
* data-amount
* shipping-address
