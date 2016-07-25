<div class="row">
    <div class="column">
        <form data-abide id="checkout-popup-options" data-product-id="<?=$product_id;?>">
            <?= $this->build($fields_template); ?>
        </form>
    </div>
    <div class="column">
        <input type="button" onclick="lightning.modules.checkout.addItemPopupOptions();" class="button medium right" value="Add to Cart">
    </div>
</div>
