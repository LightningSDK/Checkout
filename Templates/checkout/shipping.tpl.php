<div class="row">
    <div class="small-12 medium-6 medium-offset-3 column">
        <?= \Lightning\View\CMS::embed('checkout_shipping_header', [
                'default' => '
                <h2 class="text-center">Where should we ship it?</h2>
                <p style="text-align: center"><img src="/images/checkout/secure-payments.png" style="height:60px;"></p>
                ',
        ]); ?>

        <form data-abide method="post" action="/store/checkout">
            <?= \Lightning\Tools\Form::renderTokenInput(); ?>
            <?php $shipping = $cart->getShippingAddress(); ?>
            <input type="hidden" name="action" value="shipping">

            <div>
                <label>Email:</label>
                <input name="email" type="email" required value="<?= $cart->getUser()->email ?? ''; ?>">
                <small class="error">Please enter a valid email</small>
            </div>

            <div>
                <label>Name:</label>
                <input name="name" required value="<?= !empty($shipping->name) ? $shipping->name : ''; ?>">
                <small class="error">Please enter your name</small>
            </div>

            <div>
                <label>Address:</label>
                <input name="street" required value="<?= !empty($shipping->street) ? $shipping->street : ''; ?>">
                <small class="error">Please enter your address</small>
            </div>

            <div>
                <label>Address (cont):</label>
                <input name="street2" value="<?= !empty($shipping->street2) ? $shipping->street2 : ''; ?>">
            </div>

            <div>
                <label>City:</label>
                <input name="city" required value="<?= !empty($shipping->city) ? $shipping->city : ''; ?>">
                <small class="error">Please enter your city</small>
            </div>

            <div>
                <label>Country:</label>
                <?= \Lightning\View\Field\Location::countryPop('country', 'required', !empty($shipping->country) ? $shipping->country : 'US'); ?>
                <small class="error">Please select your country</small>
            </div>
            <div>
                <label>State:</label>
                <div id="state_container">
                    <small class="error">Please select your state</small>
                </div>
            </div>

            <div>
                <label>Postal Code:</label>
                <input name="zip" required value="<?= !empty($shipping->zip) ? $shipping->zip : ''; ?>">
                <small class="error">Please enter your postal code</small>
            </div>

            <div class="text-right">
                <input type="submit" name="submit" class="button red medium" value="Continue">
            </div>
        </form>
    </div>
</div>