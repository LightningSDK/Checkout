<table>
    <tr>
        <th>User</th>
        <th>Amount Due</th>
    </tr>
    <?php foreach ($affiliates as $affiliate): ?>
    <tr>
        <td><?= $affiliate['email']; ?></td>
        <td style="text-align: right">$<?= number_format($affiliate['total']/100, 2); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
