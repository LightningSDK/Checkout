<div class="row">
    <form data-abide id="checkout-popup-options" class="checkout-form" data-product-id="<?=$product->id;?>">
        <?= !empty($fields_template) ? $this->build($fields_template) : ''; ?>
    </form>
</div>
<div class="children"></div>
<div class="price text-right"></div>
<span class="button medium right red checkout-product" data-checkout-product-id="<?= $product->id; ?>" data-checkout="<?= !empty($product->options['button_type']) ? $product->options['button_type'] : 'add-to-cart'; ?>" <?php if (!empty($product->subscription)): ?>data-create-customer="true"<?php endif; ?>>
        <?= !empty($product->options['button_text']) ? $product->options['button_text'] : 'Add to Cart'; ?>
            </span>
