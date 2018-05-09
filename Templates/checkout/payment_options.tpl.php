<div class="row payment-selection">
    <div class="small-12 large-10 large-offset-1 column">
        <h2 class="text-center">How would you like to pay?</h2>
        <?php foreach(\Lightning\Tools\Configuration::get('modules.checkout.handlers') as $gateway => $handler):
            if (is_string($handler)) {
                $handler = new $handler();
            }
            elseif (is_array($handler)) {
                $handler = new $handler['connector']();
            }
            ?>
            <div class="row-selector">
                <div class="description column small-12 large-8"><?= $handler->getDescription(); ?></div>
                <div class="button-container small-12 large-4 text-center column">
                    <a class="button blue medium" href="/store/checkout?page=payment&gateway=<?= $gateway; ?>"><?= $handler->getTitle(); ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
