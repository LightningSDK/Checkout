<h2>How would you like to pay?</h2>
<div class="row">
<?php foreach(\Lightning\Tools\Configuration::get('modules.checkout.handlers') as $gateway => $handler):
    if (is_string($handler)) {
        $handler = new $handler();
    }
    elseif (is_array($handler)) {
        $handler = new $handler['connector']();
    }
    ?>
    <div class="row-selector">
        <div class="description column small-12 medium-8"><?= $handler->getDescription(); ?></div>
        <div class="button-container small-12 medium-4 text-center">
            <a href="/checkout?page=payment&gateway=<?= $gateway; ?>"><?= $handler->getTitle(); ?></a>
        </div>
    </div>
<?php endforeach; ?>
</div>
