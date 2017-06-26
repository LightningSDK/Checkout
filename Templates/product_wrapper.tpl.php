<div class="row">
    <div class="column">
        <ul class="breadcrumbs">
            <li><a href="/store">Store</a></li>
            <?php if (!empty($product->options['breadcrumbs'])):
                foreach ($product->options['breadcrumbs'] as $url => $name): ?>
                    <li><a href="<?=$url;?>"><?= $name; ?></a></li>
            <?php endforeach; endif; ?>
            <li class="current"><a href="#"><?=$product->title;?></a></li>
        </ul>
        <a href=""></a>
    </div>
</div>
<?= $this->build($product_template); ?>
