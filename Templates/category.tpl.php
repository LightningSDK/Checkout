<?= $this->build(['breadcrumbs', 'Checkout']); ?>
<ul class="small-block-grid-2 medium-block-grid-3 large-block-grid-4 category-products">
    <?php if (!empty($products)): ?>
        <?php foreach ($products as $product): ?>
        <li class="column small-12 medium-4 large-3 left product">
            <a href="/store/<?= $product->url; ?>"><img src="<?= $product->getImage(); ?>">
            <br><strong><?= $product->title; ?></strong></a>
        </li>
        <?php endforeach; ?>
    <?php else: ?>
        <h2>Sorry, there's nothing here at the moment. Try back later.</h2>
    <?php endif; ?>
</ul>
<?php if (!empty($gallery)){
    $this->build(['gallery', 'PhotoGallery']);
} ?>
