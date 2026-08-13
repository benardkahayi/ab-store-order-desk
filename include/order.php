<table>
<tr>
<th>ID</th><th>Name</th><th>Phone</th><th>Order ID</th><th>Product</th><th>Status</th><th>Date</th><th>Actions</th>
</tr>
<?php foreach($orders as $o): ?>
<tr>
<td><?= $o['id'] ?></td>
<td><?= $o['customer_name'] ?></td>
<td><?= $o['phone'] ?></td>
<td><?= $o['order_id'] ?></td>
<td><?= $o['product_name'] ?></td>
<td><?= $o['status'] ?></td>
<td><?= $o['date_created'] ?></td>
<td>
    <a href="edit_order.php?id=<?= $o['id'] ?>" class="btn-edit"><i class="bi bi-pencil-square"></i></a>
    <a href="delete_order.php?id=<?= $o['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></a>
</td>
</tr>
<?php endforeach; ?>
</table>
