<form data-abide="ajax" id="checkout-popup-options" data-product-id="<?=$product->id;?>" action="/optin" data-ajax-action="/api/optin">
    <div class="text-center">
        <span class="button medium red inline-block">This item is temporarily unavailable.</span>
    </div>
    <div class="loading-container optin clearfix frame">
        Want to know when this item is available?
        <form action="/user" method="post" id="register" data-abide>
            <?= \Lightning\Tools\Form::renderTokenInput(); ?>
            <input type="hidden" name="list" value="<?=\Source\Model\Message::getListIDByName('Product Waiting');?>" />
            <div>
                <label>Your Name:
                    <input type="text" name="name" id='name' value="<?=\Lightning\View\Field::defaultValue('name');?>" required />
                </label>
                <small class="error">Please enter your name.</small>
            </div>
            <div>
                <label>Your Email:
                    <input type="email" name="email" id='email' value="<?=\Lightning\View\Field::defaultValue('email');?>" required />
                </label>
                <small class="error">Please enter your email.</small>
            </div>
            <div class="text-center">
                <input name="submit" type="submit" class="button medium red" style="display: inline-block" value="Let me know!" />
            </div>
        </form>
    </div>
</form>
