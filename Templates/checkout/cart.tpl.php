<?php
/* @var $cart \lightningsdk\checkout\Model\Order */
?>
<div class="checkout-cart-container">
<table class="checkout-cart">
    <tr>
        <td></td>
        <td class="qty">Qty</td>
        <td class="hide-for-small">Item</td>
        <td class="amount">Amount</td>
        <td class="item-total">Total</td>
    </tr>
    <?php
    /* @var $item \lightningsdk\checkout\Model\LineItem */
    foreach ($cart->getItems() as $item):
    $description = '<strong>' . $item->title . '</strong>';
    if (!empty($item->description)) {
        $description .= '<br>' . $item->description;
    }
    if ($options = $item->getHTMLFormattedOptions()) {
        $description .= '<br>' . $options;
    }
    ?>
    <tr class="small-description">
        <td colspan="4"><?= $description; ?></td>
    </tr>
    <tr class="checkout-item" data-product-id="<?= $item->product_id; ?>" data-order-item-id="<?= $item->checkout_order_item_id; ?>">
        <td class="remove"><img src="/images/lightning/remove2.png" title="Remove" /></td>
        <td class="qty"><input name="checkout-qty" class="checkout-qty" value="<?= $item->qty; ?>" size="4"></td>
        <td class="title hide-for-small"><?= $description; ?></td>
        <td class="amount">$<?= number_format($item->getPrice(), 2); ?></td>
        <td class="item-total">$<?= number_format($item->getPrice() * $item->qty, 2); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($cart->getTax()): ?>
        <tr class="final-rows">
            <td colspan="2"></td>
            <td class="hide-for-small"></td>
            <td>Tax:</td>
            <td>$<?= number_format($cart->getTax(), 2); ?></td>
        </tr>
    <?php endif; ?>
    <?php if ($cart->getShipping()): ?>
    <tr class="final-rows">
        <td colspan="2"></td>
        <td class="hide-for-small"></td>
        <td>Shipping:</td>
        <td>$<?= number_format($cart->getShipping(), 2); ?></td>
    </tr>
    <?php endif; ?>
    <?php if (\lightningsdk\core\Tools\Configuration::get('modules.checkout.enable_discounts')): ?>
        <?php $discountsField = '<div class="row">
            <div class="large-4 medium-12 column">
                <span class="form-inline">Add a discount:</span>
            </div>
            <div class="large-4 medium-6 column">
                <input type="text" name="discount" value="" id="cart-discount" />
            </div>
            <div class="large-4 medium-6 column">
                <span class="button form-inline" onclick="lightning.modules.checkout.addDiscount($(\'#cart-discount\').val())">Add Discount</span>
                <div class="discount-result"></div></div>
            </div>'; ?>
        <?php if (!empty($cart->getDiscountDescriptions())) : ?>
            <tr class="final-rows hide-for-small">
                <td colspan="3" class="hide-for-small"><?= $discountsField; ?></td>
                <td>Discounts:</td>
                <td>$<?= number_format($cart->getDiscounts(), 2); ?></td>
            </tr>
            <tr class="final-rows small-description">
                <td colspan="2"></td>
                <td>Discounts:</td>
                <td>$<?= number_format($cart->getDiscounts(), 2); ?></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="3"><?= $discountsField; ?></td></tr>
        <?php endif; ?>
    <?php endif; ?>
    <tr class="final-rows">
        <td colspan="2"></td>
        <td class="hide-for-small"></td>
        <td>Total:</td>
        <td>$<?= $cart->getTotal(); ?></td>
    </tr>
</table>
<div class="checkout-buttons">
    <span class="button medium checkout-update-total">Update Total</span><span class="button-spacer"></span>
    <span class="button red medium checkout-pay">Checkout</span>
</div>
</div>
