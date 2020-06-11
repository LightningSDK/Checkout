product
<ul class="<?= $options['ul-class'] ?? ''; ?>">
    <?php foreach ($categories as $cat): ?>
        <li class="item">
            <a href="/store/' . $product->url . '">
                <img src="' . $product->getImage() . '" style="border-radius: 10px;" alt="' . Scrub::toHTML($product->title) . '"><br>
            </a>
            <h3><a href=""><?= $cat->title; ?></a></h3>
        </li>
    <?php endforeach; ?>
</ul>
