<div class="cell small-up-1 medium-up-3 large-up-4 category-products">
    <?php foreach ($products as $product): ?>
        <div class="cell product">
            <a href="<?= \lightningsdk\core\Tools\Configuration::get('web_root') ?>/store/<?= $product->url; ?><?= !empty($ref) ? '?ref=' . $ref : '' ?>" target="_top"><img src="<?= $product->getImage(); ?>">
                <br><strong><?= $product->title; ?></strong></a>
        </div>
    <?php endforeach; ?>
</div>
