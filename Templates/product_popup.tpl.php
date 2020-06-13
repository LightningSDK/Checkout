<div class="grid-x grid-padding-x">
    <div class="cell">
        <h1><?= $product->title; ?></h1>
        <div class="medium-6 cell options-image">
        </div>
        <div class="medium-6 cell options-fields">
            <?= $product->renderCheckoutOptions(); ?>
        </div>
    </div>
</div>
