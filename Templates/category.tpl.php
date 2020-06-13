<?= $this->build(['breadcrumbs', 'lightningsdk/checkout']); ?>
category
<?php if (!empty($categories)): ?>
    <div class="grid-x grid-padding-x grid-padding-y small-up-2 medium-up-3 large-up-4 category-products text-center">
        <?php foreach ($categories as $category): ?>
            <div class="product cell">
                <a href="/store/<?= $category->url; ?>"><img src="<?= $category->getImage(); ?>" alt="<?= \lightningsdk\core\Tools\Scrub::toHTML($category->name); ?>">
                    <br><strong><?= $category->name; ?></strong></a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($products)): ?>
    <div class="grid-x grid-padding-x grid-padding-y small-up-2 medium-up-3 large-up-4 category-products text-center">
        <?php foreach ($products as $product): ?>
            <div class="product cell">
                <a href="/store/<?= $product->url; ?>"><img src="<?= $product->getImage(); ?>" alt="<?= \lightningsdk\core\Tools\Scrub::toHTML($product->title); ?>">
                <br><strong><?= $product->title; ?></strong></a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($products) && empty($categories)): ?>
    <h2>Sorry, there's nothing here at the moment. Try back later.</h2>
<?php endif; ?>

<?php if (!empty($gallery)){
    $this->build(['gallery', 'lightningsdk/photogallery']);
} ?>
