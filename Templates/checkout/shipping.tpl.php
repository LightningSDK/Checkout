<div class="grid-x grid-padding-x">
    <div class="small-12 medium-6 medium-offset-3 cell">
        <?= \lightningsdk\core\View\CMS::embed('checkout_shipping_header', [
                'default' => '
                <h2 class="text-center">Where should we ship it?</h2>
                <p style="text-align: center"><img src="/images/checkout/secure-payments.png" style="height:60px;"></p>
                ',
        ]); ?>

        <form data-abide method="post" action="/store/checkout">
            <?= \lightningsdk\core\Tools\Form::renderTokenInput(); ?>
            <?php $shipping = $cart->getShippingAddress(); ?>
            <input type="hidden" name="action" value="shipping">

            <div>
                <label>Email:
                    <input name="email" type="email" required value="<?= $cart->getUser()->email ?? ''; ?>">
                    <span class="form-error">Please enter a valid email</span>
                </label>
            </div>

            <div>
                <label>Name:
                    <input name="name" type="text" required value="<?= !empty($shipping->name) ? $shipping->name : ''; ?>">
                    <span class="form-error">Please enter your name</span>
                </label>
            </div>

            <div>
                <label>Address:
                    <input name="street" type="text" required value="<?= !empty($shipping->street) ? $shipping->street : ''; ?>">
                    <span class="form-error">Please enter your address</span>
                </label>
            </div>

            <div>
                <label>Address (cont):
                    <input name="street2" type="text" value="<?= !empty($shipping->street2) ? $shipping->street2 : ''; ?>">
                </label>
            </div>

            <div>
                <label>City:
                    <input name="city" type="text" required value="<?= !empty($shipping->city) ? $shipping->city : ''; ?>">
                    <span class="form-error">Please enter your city</span>
                </label>
            </div>

            <div>
                <label>Country:
                    <?= \lightningsdk\core\View\Field\Location::countryPop('country', 'required', !empty($shipping->country) ? $shipping->country : 'US'); ?>
                    <span class="form-error">Please select your country</span>
                </label>
            </div>
            <div>
                <label>State:
                    <div id="state_container">
                    </div>
                    <span class="form-error">Please select your state</span>
                </label>
            </div>

            <div>
                <label>Postal Code:
                    <input name="zip" type="text" required value="<?= !empty($shipping->zip) ? $shipping->zip : ''; ?>">
                    <span class="form-error">Please enter your postal code</span>
                </label>
            </div>

            <div class="text-right">
                <input type="submit" name="submit" class="button red medium" value="Continue">
            </div>
        </form>
    </div>
</div>