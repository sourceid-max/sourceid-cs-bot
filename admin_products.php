<?php
$products = loadProducts();
?>

<div class="card">
    <div class="card-header">Product List</div>
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p>No products found.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Tags</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['id']) ?></td>
                            <td><?= htmlspecialchars($product['nama']) ?></td>
                            <td><?= htmlspecialchars($product['harga']) ?></td>
                            <td><?= htmlspecialchars($product['kategori']) ?></td>
                            <td><?= htmlspecialchars($product['tag']) ?></td>
                            <td><?= htmlspecialchars($product['keterangan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h4>Note</h4>
            <p>Products are managed via the <code>product.txt</code> file.<br>
            Edit this file directly to add, modify, or remove products.<br>
            Function product and order inquery info not in this version.<br>
            No DB connection, No Wocommerence compatible. (May be will in pro version)</p><br>
            <p><strong>Format:</strong> ID|Name|Price|Category|Tags|Description</p>
        </div>
    </div>
</div>