<div class="row">
    <form data-abide id="checkout-popup-options" data-product-id="<?=$product->id;?>">
        <?= $this->build($fields_template); ?>
        <div class="medium-6 right column">
            <span onclick="lightning.modules.checkout.addItemPopupOptions();" class="button medium right red">Add to Cart</span>
        </div>
    </form>
</div>
