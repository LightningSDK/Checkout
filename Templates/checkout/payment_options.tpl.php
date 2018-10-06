<div class="row payment-selection">
    <div class="small-12 large-10 large-offset-1 column">
        <?php if (empty($handlers)): ?>
            <h2 class="text-center">There are no payment handlers configured.</h2>
        <?php else: ?>
            <h2 class="text-center">How would you like to pay?</h2>

            <?php foreach($handlers as $gateway => $handler): ?>
                <div class="row-selector">
                    <div class="description column small-12 large-8"><?= $handler->getDescription(); ?></div>
                    <div class="button-container small-12 large-4 text-center column">
                        <a class="button blue medium" href="/store/checkout?page=payment&gateway=<?= $gateway; ?>"><?= $handler->getTitle(); ?></a>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>
</div>
