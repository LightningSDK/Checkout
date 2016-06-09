<form method="post" action="/admin/orders">
    <?= \Lightning\Tools\Form::renderTokenInput(); ?>
    <input type="hidden" name="action" value="ship" />
    <input type="hidden" name="id" value="<?=$order['order_id'];?>">
    <h2>Would you like you ship this order:</h2>
    <p>Order ID: <?= $order['order_id']; ?></p>
    <p>Ship to:<br>
        <?= $shipping_address['name']; ?><br>
        <?= $user['email']; ?><br>
        <?= $shipping_address['street']; ?><br>
        <?= $shipping_address['street2']; ?><br>
        <?= $shipping_address['city']; ?>,
        <?= $shipping_address['state']; ?>
        <?= $shipping_address['zip']; ?>
    </p>
    <p><strong>Package:</strong>
        Dimensions: <input type="text" name="package-length"><br>
        <input type="text" name="package-height"><br>
        <input type="text" name="package-width"><br>
        Weight: <input type="text" name="package-weight"><br>
        <select name="package-weight-units">
            <option value="oz">Ounces</option>
            <option value="lb">Pounds</option>
        </select>
    </p>
    <p><label><input type="checkbox" name="notify" value="true"> Notify User</label></p>
    <p><label><input type="checkbox" name="print-label" value="true"> Print Label</label></p>
    <a href="/admin/orders" class="button">Cancel</a> <input type="submit" name="submit" value="Ship" class="button red" />
</form>
