<?php $this->dontCache(); ?>
<div class="grid-x grid-padding-x">
    <div class="cell">
        <form method="post">
            <?= \lightningsdk\core\Tools\Form::renderTokenInput(); ?>
            <h3>
                Are you sure you want to mark this order as fulfilled?
            </h3>
            <input type="hidden" name="id" value="<?= $order_id; ?>">
            <div>
                <a href="/admin/orders" class="button medium">No</a>
                <input type="submit" name="submit" value="Mark as Fulfilled" class="button medium red">
            </div>
        </form>
    </div>
</div>
