<div class="grid-x grid-padding-x payment-selection">
    <div class="small-12 large-10 large-offset-1 cell">
        <?php if (empty($handlers)): ?>
            <h2 class="text-center">There are no payment handlers configured.</h2>
        <?php else: ?>
            <h2 class="text-center">How would you like to pay?</h2>

            <?php foreach($handlers as $gateway => $handler): ?>
                <div class="row-selector">
                    <div class="description cell small-12 large-8 text-center"><?= $handler->getDescription(); ?><br>
                    <img src="<?= $handler->getLogo(); ?>" style="height: 40px; margin:20px;" />
                    </div>
                    <div class="button-container small-12 large-4 text-center cell">
                        <a class="button blue medium" href="/store/checkout?page=payment&gateway=<?= $gateway; ?>"><?= $handler->getTitle(); ?></a>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>
</div>
