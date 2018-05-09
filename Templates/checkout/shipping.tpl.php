<div class="row">
    <div class="small-12 medium-6 medium-offset-3 column">
        <h2 class="text-center">Where should we ship it?</h2>
        <form data-abide method="post" action="/store/checkout">
            <?= \Lightning\Tools\Form::renderTokenInput(); ?>
            <?php $shipping = $cart->getShippingAddress(); ?>
            <input type="hidden" name="action" value="shipping">

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
                <label>State:</label>
                <?= \Lightning\View\Field\Location::statePop('state', !empty($shipping->state) ? $shipping->state : '', 'required'); ?>
                <small class="error">Please select your state</small>
            </div>

            <div>
                <label>Postal Code:</label>
                <input name="zip" required value="<?= !empty($shipping->zip) ? $shipping->zip : ''; ?>">
                <small class="error">Please enter your postal code</small>
            </div>

            <div>
                <label>Country:</label>
                <?= \Lightning\View\Field\Location::countryPop('country', 'required', !empty($shipping->country) ? $shipping->country : 'US'); ?>
                <small class="error">Please select your country</small>
            </div>

            <div class="text-right">
                <input type="submit" name="submit" class="button red medium" value="Next">
            </div>
        </form>
    </div>
</div>