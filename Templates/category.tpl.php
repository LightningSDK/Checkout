<?= $this->build(['breadcrumbs', 'Checkout']); ?>
category
<?php if (!empty($categories)): ?>
    <ul class="grid-x grid-margin-x grid-margin-y small-up-2 medium-up-3 large-up-4 category-products text-center">
        <?php foreach ($categories as $category): ?>
            <li class="product cell">
                <a href="/store/<?= $category->url; ?>"><img src="<?= $category->getImage(); ?>" alt="<?= \lightningsdk\core\Tools\Scrub::toHTML($category->name); ?>">
                    <br><strong><?= $category->name; ?></strong></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($products)): ?>
    <ul class="grid-x grid-margin-x grid-margin-y small-up-2 medium-up-3 large-up-4 category-products text-center">
        <?php foreach ($products as $product): ?>
            <li class="product cell">
                <a href="/store/<?= $product->url; ?>"><img src="<?= $product->getImage(); ?>" alt="<?= \lightningsdk\core\Tools\Scrub::toHTML($product->title); ?>">
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
