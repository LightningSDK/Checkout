(function(){
    var self;
    lightning.modules.checkout = {
        contents: {},
        requestId: 0,
        lastRequestId: 0,
        cartIcon: null,
        init: function() {
            $('.checkout-product').click(this.click);
            var request_id = ++self.requestId;
            $.ajax({
                url : 'api/cart',
                dataType: 'json',
                success: function(data) {
                    self.processUpdatedCart(data, request_id, true);
                }
            });

            // Make sure the modal is present prior to attaching event listeners.
            lightning.dialog.init();
            // Add an event handler when clicking to remove an item.
            $('.reveal-modal').on('click', '.remove img', self.removeItem)
                .on('change', '.checkout-qty', self.updateQty)
                .on('click', '.checkout-update-total', self.updateQtys)
                .on('click', '.checkout-pay', self.pay);
        },

        click: function(event) {
            var button = $(event.target);
            if (button.data('checkout') == 'add-to-cart') {
                var request_id = ++self.requestId;
                $.ajax({
                    url: 'api/cart',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        product_id: button.data('checkout-product-id'),
                        qty: 1,
                        options: {
                            test: 'option 1'
                        },
                        action: 'add-to-cart',
                    },
                    success: function(data) {
                        self.processUpdatedCart(data, request_id);
                    }
                });
            } else {
                // Pay Now
            }
        },

        removeItem: function(event) {
            var target = $(event.target);
            var row = target.closest('tr');
            var request_id = ++self.requestId;
            $.ajax({
                url: 'api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    product_id: row.data('product-id'),
                    options: {
                        test: 'option 1'
                    },
                    action: 'remove-item',
                },
                success: function(data) {
                    self.processUpdatedCart(data, request_id);
                }
            });
        },

        updateQty: function(event) {
            var target = $(event.target);
            var row = target.closest('tr');
            var new_qty = parseInt(target.val());
            if (isNaN(new_qty)) {
                return;
            }
            var request_id = ++self.requestId;
            $.ajax({
                url: 'api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    product_id: row.data('product-id'),
                    options: {
                        test: 'option 1'
                    },
                    action: 'set-qty',
                    qty: new_qty
                },
                success: function(data) {
                    self.processUpdatedCart(data, request_id);
                }
            });
        },

        updateQtys: function(event) {
            // Get all the qtys to send.
            var items = [];
            $('.checkout-item').each(function(){
                var qty_box = $(this).find('.checkout-qty');
                var qty = parseInt(qty_box.val());
                if (isNaN(qty)) {
                    qty_box.val(1);
                    qty = 1;
                }
                items.push({
                    product_id: $(this).data('product-id'),
                    options: {
                        test: 'option 1',
                    },
                    qty: qty,
                });
            });

            // Update the qtys.
            var request_id = ++self.requestId;
            $.ajax({
                url: 'api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'set-qtys',
                    items: items
                },
                success: function(data) {
                    self.processUpdatedCart(data, request_id);
                }
            });
        },

        processUpdatedCart: function(data, request_id, suppress_display) {
            if (suppress_display == undefined) {
                suppress_display = false;
            }
            if (request_id > self.lastRequestId) {
                self.lastRequestId = request_id;
                self.updateCart(data.cart);
                if (!suppress_display) {
                    self.showCart();
                }
            }
        },

        pay: function() {
            var paymentHandler = lightning.get('modules.checkout.handler');
            if (paymentHandler) {
                var handler = lightning.getMethodReference(paymentHandler);
                handler({
                    amount: self.contents.total,
                    cart_id: self.contents.id,
                }, function(){
                    self.cartIcon.find('.item-count').html(0);
                    self.cartIcon.removeClass('show');
                });
            }
        },

        updateCart: function(data) {
            // Update the cart contents.
            self.contents = data;
            self.contents.total
                = self.contents.subtotal
                + self.contents.shipping
                + self.contents.tax;

            if (self.contents.items.length > 0) {
                // If there are items in the cart, show the cart button.
                if (!self.cartIcon) {
                    // Create the cart icon.
                    self.initIcon();
                }
                self.cartIcon.find('.item-count').html(self.contents.items.length);
                self.cartIcon.addClass('show');
            } else {
                // If the cart is empty, hide the cart button.
                if (self.cartIcon) {
                    self.cartIcon.find('.item-count').html(0);
                    self.cartIcon.removeClass('show');
                }
            }
        },

        initIcon: function() {
            var icon = $('<div id="checkout-side-icon"><div class="container"><i class="fa fa-shopping-cart"></i><div class="item-count">' + self.contents.items.length + '</div></div></div>');
            icon.appendTo('body');
            self.cartIcon = $('#checkout-side-icon').addClass('show').click(self.showCart);
            self.cartIcon.click(self.showCart);
        },

        showCart: function() {
            var data = self.contents;
            var content = '';
            if (data.items && data.items.length > 0) {
                content += '<table class="checkout-cart">';
                content += '<tr><td></td><td class="qty">Qty</td><td>Item</td><td class="amount">Amount</td><td class="item-total">Total</td></tr>'
                for (var i in data.items) {
                    content += '<tr class="checkout-item" data-product-id="' + data.items[i].product_id + '" data-options="' + data.items[i].options + '">';
                    content += '<td class="remove"><img src="/images/lightning/remove2.png"></td>';
                    content += '<td class="qty"><input name="checkout-qty" class="checkout-qty" value="' + data.items[i].qty + '" size="4"></td>';
                    content += '<td class="title"><strong>' + data.items[i].title + '</strong><br>' + (data.items[i].description ? data.items[i].description : '') + '</td>';
                    content += '<td class="amount">$' + parseFloat(data.items[i].price).toFixed(2) + '</td>';
                    content += '<td class="item-total">$' + (data.items[i].price * data.items[i].qty).toFixed(2) + '</td>';
                    content += '</tr>';
                }
                if (data.tax && data.tax > 0) {
                    content += '<tr class="final-rows"><td colspan="4">Tax:</td><td>$' + data.tax.toFixed(2) + '</td></tr>';
                }
                if (data.shipping && data.shipping > 0) {
                    content += '<tr class="final-rows"><td colspan="4">Shipping:</td><td>$' + data.shipping.toFixed(2) + '</td></tr>';
                }
                content += '<tr class="final-rows"><td colspan="4">Total:</td><td>$' + data.total.toFixed(2) + '</td></tr>';
                content += '</table>';
                content += '<div class="checkout-buttons">' +
                    '<span class="button medium checkout-update-total">Update Total</span><span class="button-spacer"></span>' +
                    '<span class="button red medium checkout-pay">Complete Order</span>' +
                    '</div>';
            } else {
                content = '<div><h2>Your cart is empty.</h2></div>';
            }
            lightning.dialog.showContent(content);
        }
    };
    self = lightning.modules.checkout;
})();
