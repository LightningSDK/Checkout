<?php use Lightning\Tools\Configuration; ?>
<div class="row">
    <div class="column">
        This order requires multiple fulfillment handlers:
        <ul>
            <?php foreach ($handlers as $handler): ?>
                <?php $settings = Configuration::get('modules.checkout.fulfillment_handlers.' . $handlers[0]); ?>
                <li><a href="?action=ship&handler=<?= $handler; ?>&id=<?= $order_id; ?>"><?= $handler; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>