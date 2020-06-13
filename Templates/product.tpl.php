<div class="grid-x grid-padding-x">
    <div class="cell">
        <h1><?= $product->title; ?></h1>
        <div class="medium-6 cell options-image">
        </div>
        <div class="medium-6 cell options-fields">
            <?= $product->renderCheckoutOptions(); ?>
        </div>
        <div class="cell">
            <p><?= $product->description; ?></p>
        </div>
    </div>
</div>
<?php if (!empty($gallery)){
    $this->build(['gallery', 'lightningsdk/photogallery']);
} ?>
