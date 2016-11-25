<div class="row">
    <div class="column">
        <ul class="breadcrumbs">
            <li><a href="/store">Store</a></li>
            <?php if (!empty($category->breadcrumbs)):
                foreach ($category->breadcrumbs as $url => $name): ?>
                    <li><a href="<?=$url;?>"><?= $name; ?></a></li>
                <?php endforeach; endif; ?>
            <li class="current"><a href="#"><?=$category->name;?></a></li>
        </ul>
        <a href=""></a>
    </div>
</div>
<ul class="small-block-grid-2 medium-block-grid-3 large-block-grid-4">
    <?php foreach ($products as $product): ?>
    <li class="column small-12 medium-4 large-3 left">
        <a href="/store/<?= $product->url; ?>"><img src="<?= $product->getImage(); ?>">
        <br><strong><?= $product->title; ?></strong></a>
    </li>
    <?php endforeach; ?>
</ul>
