(function(){
    if (lightning.modules.checkout) {
        return;
    }
    var self = lightning.modules.checkout = {
        contents: {},
        requestId: 0,
        lastRequestId: 0,
        cartIcon: null,
        init: function() {
            $('body')
                .on('click', '.checkout-product', self.click)
                .on('click', '.options-image .selection img', self.selectImage);
            var request_id = ++self.requestId;
            $.ajax({
                url : '/api/cart',
                dataType: 'json',
                success: function(data) {
                    self.processUpdatedCart(data, request_id, true);
                }
            });

            // Make sure the modal is present prior to attaching event listeners.
            lightning.dialog.init();
            // Add an event handler when clicking to remove an item.
            // .reveal-modal handles all events in a popup
            // .checkout-cart-container handles all events on the checkout page
            $('.reveal-modal,.checkout-cart-container').on('click', '.remove img', self.removeItem)
                .on('change', '.checkout-qty', self.updateQty)
                .on('click', '.checkout-update-total', self.updateQtys)
                .on('click', '.checkout-pay', self.payCart);
        },

        click: function(event) {
            var button = $(event.currentTarget);
            var product_id = button.data('checkout-product-id');
            var purchase_options = {};
            var line_item_options = {};

            // If there are options already on the page, load them.
            var form = button.closest('.options-fields');
            if (form.length === 1) {
                var elems = form.find('input, textarea, select').not(":hidden, [data-abide-ignore]").get();
                Foundation.libs.abide.validate(elems, form, true);
                if (form.find('div.error').length === 0) {
                    form.find('input,select,textarea').each(function(index, item){
                        line_item_options[item.name] = item.value;
                    });
                } else {
                    return;
                }
            }

            lightning.dialog.showLoader();

            if (button.data('checkout') === 'add-to-cart') {
                // Attempt to add the item to the cart.
                self.addItem(product_id, 1, line_item_options);
            } else {
                // Pay Now
                if (button.data('create-customer') === true) {
                    purchase_options.create_customer = true;
                }
                if (button.data('redirect')) {
                    purchase_options.redirect = button.data('redirect');
                }
                if (button.data('title')) {
                    purchase_options.title = button.data('title');
                }
                if (button.data('amount')) {
                    purchase_options.amount = button.data('amount');
                }
                if (button.data('shipping-address') === true) {
                    purchase_options.shipping_address = true;
                }
                // TODO: Move this to stripe module
                if (lightning.get('modules.checkout.bitcoin', false)) {
                    purchase_options.bitcoin = true;
                }

                // Attempt to start the process.
                if (product_id) {
                    // If a product ID is specified, check with the server.
                    purchase_options.product_id = product_id;
                    purchase_options.item_options = line_item_options;
                    self.purchaseItem(product_id, 1, purchase_options);
                } else {
                    // If no product ID is specified, process the transaction.
                    self.pay(purchase_options, function(){
                        if (purchase_options.redirect) {
                            window.location = purchase_options.redirect;
                        }
                    });
                }
            }
        },

        purchaseItem: function(product_id, qty, purchase_options) {
            $.ajax({
                url: '/api/cart',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'pay-now',
                    product_id: product_id,
                    qty: qty,
                    options: purchase_options.item_options
                },
                success: function(data){
                    if (data.status === 'success') {
                        purchase_options.product_id = product_id;
                        purchase_options.product_options = data.options;
                        self.pay(purchase_options, function(){
                            if (purchase_options.redirect) {
                                window.location = purchase_options.redirect;
                            }
                        });
                    }
                }
            });
        },

        addItem: function(product_id, qty, line_item_options) {
            var request_id = ++self.requestId;
            lightning.dialog.showLoader('Adding this item to your cart...');
            $.ajax({
                url: '/api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    product_id: product_id,
                    qty: qty,
                    options: line_item_options,
                    action: 'add-to-cart',
                },
                success: function(data) {
                    if (data.form) {
                        // There are more required options to be completed.
                        lightning.dialog.showContent(data.form);
                        self.initProductOptions(data);
                        setTimeout(function(){
                            $(document).foundation();
                        }, 500);
                    } else {
                        // The product has been added.
                        lightning.tracker.track(lightning.tracker.events.addToCart, {
                            label: product_id
                        });
                        self.processUpdatedCart(data, request_id);
                    }
                }
            });
        },

        initProductOptions: function(data) {
            self.popupOptions = data.options;
            self.basePrice = parseFloat(data.base_price);
            self.updateOptionsFormRoot();
            $('.options-fields').on('change', 'input,select', self.updateOptionsFormRoot);
        },

        /**
         * Update the options form starting from the root. This applies changes to the image and options below
         * when an option field is changd.
         */
        updateOptionsFormRoot: function() {
            self.popupImg = self.popupOptions.image ? self.popupOptions.image : null;
            self.modifiedPrice = self.basePrice;
            var optionsFields = $('.options-fields');
            self.updateOptionsForm(null, self.popupOptions, optionsFields);
            var imgContainer = $('.options-image');

            // If there is an image selected by the options:
            if (self.popupImg) {
                // Make sure the main display image exists.
                var img = imgContainer.find('.preview-image img');
                if (img.length === 0) {
                    imgContainer.append('<div class="preview-image"><img src="" /></div>');
                    img = imgContainer.find('.preview-image img');
                    self.initPhotoGallery(function(){
                        img.on('click', lightning.modules.photogallery.show).css('cursor', 'zoom-in');
                    });
                }

                // Make sure the image is an array (even if just one image)
                if (typeof self.popupImg !== 'object') {
                    self.popupImg = [self.popupImg];
                }

                var imageSet = false;
                if (self.popupImg.length > 1) {
                    // Make sure there is an image selection container.
                    var selectionContainer = imgContainer.find('.selection');
                    if (selectionContainer.length === 0) {
                        imgContainer.append('<div class="column selection"></div>');
                        selectionContainer = imgContainer.find('.selection');
                    }
                    selectionContainer.empty();
                    for (i in self.popupImg) {
                        var active = '';
                        if (self.popupImg[i] === img.prop('src')) {
                            active = 'active';
                            imageSet = true;
                        }
                        selectionContainer.append('<img src="' + self.getImageUrl(self.popupImg[i], 250) + '" class="' + active + ' gallery-image" />');
                    }
                }

                if (!imageSet) {
                    // If the image has not already been set, then active the first image.
                    img.prop('src', self.getImageUrl(self.popupImg[0], 250));
                    if (self.popupImg.length > 1) {
                        selectionContainer.find('img').first().addClass('active');
                    }
                }

                self.initPhotoGallery(function(){
                    var galleryImages = [];
                    for (var i in self.popupImg) {
                        galleryImages[i] = self.getImageUrl(self.popupImg[i], 1000);
                    }
                    lightning.modules.photogallery.setImages(galleryImages);
                });

            } else {
                imgContainer.empty();
            }
            $('.price', optionsFields).html('$' + parseFloat(self.modifiedPrice).toFixed(2));
        },

        initPhotoGallery: function(callback) {
            if (lightning.get('modules.checkout.photo_gallery')) {
                if (lightning.modules.photogallery) {
                    callback();
                } else {
                    lightning_startup(function(){
                        if (lightning.modules.photogallery) {
                            callback();
                        }
                    });
                }
            }
        },

        /**
         * Handles changing the selected image when multiple are available.
         *
         * @param e
         *   The event
         */
        selectImage: function (e) {
            var image = $(e.target);
            $('.options-image .selection img').removeClass('active');
            $('.options-image .preview-image img').prop('src', self.getImageUrl(image.addClass('active').prop('src'), 500));
        },

        getImageUrl: function (original, size) {
            if (lightning.get('modules.checkout.image_manager')) {
                var regex = /(\/image\?i=.*&s=)[0-9]+(&f=jpg)/;
                if (original.match(regex)) {
                    return original.replace(regex, '$1' + size + '$2');
                } else {
                    return '/image?' + lightning.buildQuery({
                        'i': original,
                        's': size,
                        'f': 'jpg'
                    });
                }
            } else {
                return original;
            }
        },

        /**
         * Makes actual changes to an option when the parent option is changed.
         *
         * @param {element|null} field
         *   Contains a dom element or null if this is the initial call.
         * @param {object} options
         *   The options set to be displayed below the field
         * @param {element} parent
         *   The html wrapper element where the options will go
         */
        updateOptionsForm: function(field, options, parent) {
            // Field layout:
            // div#option-NAME-wrapper
            // +--div#option-NAME-container
            //    +--label
            //    +--input|select
            // +--div.children

            var field_container;
            for (var i in options.options) {
                field_container = parent.children().filter('.children');

                // Remove any fields that are at the current level and not in the current options list.
                field_container.children().each(function(){
                    var obj = $(this);
                    if (!options.options.hasOwnProperty(obj.data('name'))) {
                        obj.remove();
                    }
                });

                // Determine the field type and other attributes.
                var field_type = 'input';
                var has_label = true;
                if (typeof options.options[i].values === 'object') {
                    field_type = 'select';
                    has_label = false;
                }
                var field_name = i.replace(/[^a-z0-9-_]/i, '');
                var display_name = options.options[i].hasOwnProperty('display_name') ? options.options[i].display_name : i;
                var previousValue = null;

                // Make sure a wrapper exists
                var wrapper = parent.find('#option-' + field_name + '-wrapper')
                if (wrapper.length === 0) {
                    // Insert the new field.
                    wrapper = $('<div id="option-' + field_name + '-wrapper">')
                        .append('<div id="option-' + field_name + '-container">')
                        .append('<div class="children">')
                        .data('name', field_name);
                    field_container.append(wrapper);
                }

                // See if the input already exists, save the value and remove it.
                var container = field_container.find('#option-' + field_name + '-container');
                var input = parent.find('#option-' + field_name);
                if (input.length > 0) {
                    // Save the previous value and remove the field.
                    previousValue = input.val();
                } else if (options.options[i].hasOwnProperty('default')) {
                    previousValue = options.options[i].default;
                }
                container.empty();

                // Create a new field.
                switch (field_type) {
                    case 'input':
                        // TODO: Add input validation to options.options[i].type == 'int'
                        input = $('<input name="' + i + '" id="option-' + field_name + '">');
                        // Set the previous value.
                        input.val(previousValue);
                        container.append('<label>' + display_name + '</label>').append(input);
                        break;
                    case 'select':
                        input = $('<select name="' + i + '" id="option-' + field_name + '">');
                        for (var j in options.options[i].values) {
                            input.append('<option value="' + j + '">' + j + '</option>');
                        }
                        // Set the previous value.
                        if ($(input).find('[value="' + previousValue + '"]').length > 0) {
                            input.val(previousValue);
                        } else {
                            input.val(input.children().first().prop('value'));
                        }
                        container.append('<label>' + display_name + '</label>').append(input);
                        break;
                }

                // Get the final selected field value.
                var value = parent.find('#option-' + field_name).val();

                // Update the child fields
                if (typeof options.options[i].values !== 'undefined' && typeof options.options[i].values[value] === 'object') {
                    // Override the images if present.
                    if (options.options[i].values[value].hasOwnProperty('image')) {
                        self.popupImg = options.options[i].values[value].image;
                    }

                    // Override the price if present.
                    if (options.options[i].values[value].hasOwnProperty('price')) {
                        var new_price = options.options[i].values[value].price;
                        if (new_price[0] === '+') {
                            self.modifiedPrice += parseFloat(new_price.substr(1));
                        } else if (new_price[0] === '-') {
                            self.modifiedPrice -= parseFloat(new_price.substr(1));
                        } else {
                            self.modifiedPrice = parseFloat(options.options[i].values[value].price);
                        }
                    }

                    // Override child options if present.
                    var child_options = {};
                    $.extend(child_options, options, options.options[i].values[value]);
                    if (!options.options[i].values[value].hasOwnProperty('options')) {
                        delete child_options.options;
                    }
                    self.updateOptionsForm(i, child_options, parent.find('#option-' + field_name + '-wrapper'));
                }
            }
        },

        removeItem: function(event) {
            var target = $(event.target);
            var row = target.closest('tr');
            var request_id = ++self.requestId;
            $.ajax({
                url: '/api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    order_item_id: row.data('order-item-id'),
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
                url: '/api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    order_item_id: row.data('order-item-id'),
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
                var row = $(this);
                var qty_box = row.find('.checkout-qty');
                var qty = parseInt(qty_box.val());
                if (isNaN(qty)) {
                    qty_box.val(1);
                    qty = 1;
                }
                items.push({
                    order_item_id: row.data('order-item-id'),
                    qty: qty,
                });
            });

            // Update the qtys.
            var request_id = ++self.requestId;
            $.ajax({
                url: '/api/cart',
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

        pay: function(options, callback) {
            var paymentHandler = lightning.get('modules.checkout.handler');
            if (paymentHandler) {
                lightning.dialog.showLoader();
                var handler = lightning.getMethodReference(paymentHandler);
                handler(options, callback);
            } else {
                lightning.dialog.show();
                lightning.dialog.add('The payment handler has not been configured for the checkout module.', 'error');
            }
        },

        payCart: function() {
            window.location = '/store/checkout?page=checkout';
        },

        updateCart: function(data) {
            if (!lightning.get('modules.checkout.hideCartModal')) {
                // Update the cart contents.
                self.contents = data;
                self.contents.total
                    = self.contents.subtotal
                    + self.contents.shipping
                    + self.contents.tax
                    + self.contents.discounts.total;

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
            }
        },

        initIcon: function() {
            if (!lightning.get('modules.checkout.hideCartModal')) {
                var icon = $('<div id="checkout-side-icon"><div class="container"><i class="fa fa-shopping-cart"></i><div class="item-count">' + self.contents.items.length + '</div></div></div>');
                icon.appendTo('body');
                self.cartIcon = $('#checkout-side-icon')
                    .addClass('show')
                    .click(function(){
                        lightning.dialog.clear();
                        self.showCart();
                    });
            }
        },

        addDiscount: function(discount) {
            $.ajax({
                url: '/api/cart',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'add-discount',
                    discount: discount,
                },
                success: function(data) {
                    self.updateCart(data.cart);
                    self.showCart();
                },
                error: function(data) {
                    $('.discount-result').html('The discount code is not valid.');
                }
            });
        },

        removeDiscount: function(discount) {

        },

        showCart: function() {
            var data = self.contents;
            var content = '', description = '';
            if (data.items && data.items.length > 0) {
                // TODO: This would be easier with something like angular.
                content += '<table class="checkout-cart">';
                content += '<tr><td></td><td class="qty">Qty</td><td class="hide-for-small">Item</td><td class="amount">Amount</td><td class="item-total">Total</td></tr>'
                for (var i in data.items) {
                    description = '<strong>' + data.items[i].title + '</strong>';
                    if (data.items[i].description) {
                        description += '<br>' + data.items[i].description;
                    }
                    if (data.items[i].options_formatted) {
                        description += '<br>' + data.items[i].options_formatted;
                    }

                    content += '<tr class="small-description"><td colspan="4">' + description + '</td></tr>';

                    content += '<tr class="checkout-item" data-product-id="' + data.items[i].product_id + '" data-order-item-id="' + data.items[i].checkout_order_item_id + '">';
                    content += '<td class="remove"><img src="/images/lightning/remove2.png" title="Remove" /></td>';
                    content += '<td class="qty"><input name="checkout-qty" class="checkout-qty" value="' + data.items[i].qty + '" size="4"></td>';
                    content += '<td class="title hide-for-small">' + description + '</td>';
                    content += '<td class="amount">$' + parseFloat(data.items[i].price).toFixed(2) + '</td>';
                    content += '<td class="item-total">$' + (data.items[i].price * data.items[i].qty).toFixed(2) + '</td>';
                    content += '</tr>';
                }
                if (data.tax && data.tax > 0) {
                    content += '<tr class="final-rows"><td colspan="2"></td><td class="hide-for-small"></td><td>Tax:</td><td>$' + data.tax.toFixed(2) + '</td></tr>';
                }
                if (data.shipping && data.shipping > 0) {
                    content += '<tr class="final-rows"><td colspan="2"></td><td class="hide-for-small"></td><td>Shipping:</td><td>$' + data.shipping.toFixed(2) + '</td></tr>';
                }
                if (lightning.vars.modules.checkout.enable_discounts) {
                    var discountsField = '<div class="row">' +
                        '<div class="large-4 medium-12 column"><span class="form-inline">Add a discount:</span></div>' +
                        '<div class="large-4 medium-6 column"><input type="text" name="discount" value="" id="cart-discount" /></div>' +
                        '<div class="large-4 medium-6 column"><span class="button form-inline" onclick="lightning.modules.checkout.addDiscount($(\'#cart-discount\').val())">Add Discount</span><div class="discount-result"></div></div>' +
                        '</div>';
                    // Show added discounts.
                    if (data.discounts && data.discounts.total) {
                        content += '<tr class="final-rows hide-for-small">' +
                            '<td colspan="3" class="hide-for-small">' + discountsField + '</td>' +
                            '<td>Discounts:</td><td>$' + data.discounts.total.toFixed(2) + '</td>' +
                            '</tr>' +
                            '<tr class="final-rows small-description">' +
                            '<td colspan="2"></td>' +
                            '<td>Discounts:</td><td>$' + data.discounts.total.toFixed(2) + '</td>' +
                            '</tr>';
                    } else {
                        content += '<tr><td colspan="3">' + discountsField + '</td></tr>';
                    }
                }
                content += '<tr class="final-rows"><td colspan="2"></td><td class="hide-for-small"></td><td>Total:</td><td>$' + data.total.toFixed(2) + '</td></tr>';
                content += '</table>';
                content += '<div class="checkout-buttons">' +
                    '<span class="button medium checkout-update-total">Update Total</span><span class="button-spacer"></span>' +
                    '<span class="button red medium checkout-pay">Complete Order</span>' +
                    '</div>';
            } else {
                content = '<div><h2>Your cart is empty.</h2></div>';
            }
            lightning.dialog.showContent(content, false);
        },

        /**
         * Change the state selector option when the country is changed.
         * This must be called on the checkout page on load.
         */
        initCountrySelection: function() {
            $('#country').on('change', self.updateState);
            self.updateState();
        },

        updateState:  function(){
            var country = $('#country').val();
            var states = lightning.get('modules.checkout.states.' + country, null);
            var container = $('#state_container');
            var select = container.find('select');
            var input = container.find('input');
            if (states === null) {
                // No options are available for this country.
                // If this field is still a select field, change it to a text field.
                if (select.length > 0) {
                    select.remove();
                }
                if (input.length === 0) {
                    container.prepend('<input type="text" name="state" id="state" required />');
                }
            } else {
                // State options are available for this country.
                // If this field is still a select field, change it to a text field.
                if (input.length > 0) {
                    input.remove();
                }
                if (select.length === 0) {
                    container.prepend('<select type="text" name="state" id="state" required></select>');
                    select = container.find('select');
                } else {
                    select.empty();
                }
                select.append('<option></option>');
                for (var i in states) {
                    select.append('<option value="' + i + '">' + states[i] + '</option>');
                }
            }
        }
    };
})();
