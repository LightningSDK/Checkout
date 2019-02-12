<div class="row">
    <div class="column">
        <h2>Your unpaid balance: $<?= number_format($balance/100, 2); ?></h2>
        <h2>Your affiliate sales:</h2>
        <?php if (empty($orders)): ?>
            <h3>You don't have any orders yet.</h3>
        <?php else: ?>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Time</th>
                    <th>Name</th>
                    <th>Commission</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= $order['order_id']; ?></td>
                    <td><?= date('m-d-Y h:i:s a', $order['time']); ?></td>
                    <td><?= $order['name']; ?></td>
                    <td class="text-right">$<?= number_format($order['commission']/100, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="column">
        <?php
$default = <<<DEFAULT
<h3>Here's your affiliate link:</h3>
<p><code>{WEB_ROOT}/?ref={USER_ID}</code></p>
DEFAULT;
echo \Lightning\View\CMS::embed('checkout-affiliate-body', [
                'default' => $default,
        ]); ?>
    </div>
</div>