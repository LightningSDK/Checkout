<?= $this->build(['breadcrumbs', 'Checkout']); ?>

<?php if (!empty($categories)): ?>
    <ul class="small-block-grid-2 medium-block-grid-3 large-block-grid-4 category-products text-center">
        <?php foreach ($categories as $category): ?>
            <li class="column small-12 medium-4 large-3 left product">
                <a href="/store/<?= $category->url; ?>"><img src="<?= $category->getImage(); ?>" alt="<?= \Lightning\Tools\Scrub::toHTML($category->name); ?>">
                    <br><strong><?= $category->name; ?></strong></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($products)): ?>
    <ul class="small-block-grid-2 medium-block-grid-3 large-block-grid-4 category-products text-center">
        <?php foreach ($products as $product): ?>
            <li class="column small-12 medium-4 large-3 left product">
                <a href="/store/<?= $product->url; ?>"><img src="<?= $product->getImage(); ?>" alt="<?= \Lightning\Tools\Scrub::toHTML($product->title); ?>">
                <br><strong><?= $product->title; ?></strong></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (empty($products) && empty($categories)): ?>
    <h2>Sorry, there's nothing here at the moment. Try back later.</h2>
<?php endif; ?>

<?php if (!empty($gallery)){
    $this->build(['gallery', 'PhotoGallery']);
} ?>
