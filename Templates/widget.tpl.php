<ul class="small-block-grid-1 medium-block-grid-3 large-block-grid-4 category-products">
    <?php foreach ($products as $product): ?>
        <li class="column small-12 medium-4 large-3 left product">
            <a href="<?= \Lightning\Tools\Configuration::get('web_root') ?>/store/<?= $product->url; ?><?= !empty($ref) ? '?ref=' . $ref : '' ?>" target="_top"><img src="<?= $product->getImage(); ?>">
                <br><strong><?= $product->title; ?></strong></a>
        </li>
    <?php endforeach; ?>
</ul>
