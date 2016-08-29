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
<div class="row">
    <?php foreach ($products as $product): ?>
    <div class="column small-12 medium-4 large-3 left">
        <a href="/store/<?= $product->url; ?>"><img src="<?= $product->getImage(); ?>">
        <br><strong><?=$product->title;?></strong></a>
    </div>
    <?php endforeach; ?>
</div>
