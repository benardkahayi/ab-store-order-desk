<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php"); exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) { $conn->prepare("DELETE FROM products WHERE id = ?")->execute([$id]); }
    header("Location: products.php?deleted=1"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name        = trim($_POST['name']);
    $price       = (float)$_POST['price'];
    $stock       = (int)$_POST['stock'];
    $description = trim($_POST['description']);
    $category    = trim($_POST['category']);
    $status      = $_POST['status'] ?? 'active';

    if ($_POST['action'] === 'add') {
        $conn->prepare("INSERT INTO products (name,price,stock,description,category,status) VALUES (?,?,?,?,?,?)")
             ->execute([$name,$price,$stock,$description,$category,$status]);
        header("Location: products.php?added=1");
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE products SET name=?,price=?,stock=?,description=?,category=?,status=? WHERE id=?")
             ->execute([$name,$price,$stock,$description,$category,$status,$id]);
        header("Location: products.php?updated=1");
    }
    exit;
}

$search          = $_GET['search']   ?? '';
$category_filter = $_GET['category'] ?? '';

if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? OR category LIKE ? OR description LIKE ? ORDER BY id DESC");
    $t = "%$search%"; $stmt->execute([$t,$t,$t]);
} elseif ($category_filter !== '') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
    $stmt->execute([$category_filter]);
} else {
    $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
}
$products = $stmt->fetchAll();

$total_products  = count($products);
$total_stock     = array_sum(array_column($products, 'stock'));
$total_value     = array_sum(array_map(fn($p) => $p['price'] * $p['stock'], $products));
$low_stock       = count(array_filter($products, fn($p) => $p['stock'] <= 10));
$categories      = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - AB FASHIONSTORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php $active_page = 'products'; include 'admin_layout.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div>
            <h1><i class="fas fa-box me-2 text-primary"></i>Products</h1>
            <p>Manage your product inventory</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i> Add Product
        </button>
    </div>

    <!-- Alerts -->
    <?php foreach (['added'=>'Product added.','updated'=>'Product updated.','deleted'=>'Product deleted.'] as $key=>$msg): ?>
    <?php if (isset($_GET[$key])): ?>
    <div class="alert alert-<?php echo $key==='deleted'?'danger':'success'; ?> alert-dismissible fade show mb-3">
        <i class="fas fa-<?php echo $key==='deleted'?'trash':'check-circle'; ?> me-2"></i> <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Total Products</p><h2 class="fw-bold mb-0"><?php echo number_format($total_products); ?></h2></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Total Stock</p><h2 class="fw-bold mb-0"><?php echo number_format($total_stock); ?></h2></div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-cubes"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Inventory Value</p><h2 class="fw-bold mb-0" style="font-size:20px;">TZS <?php echo number_format($total_value, 0); ?></h2></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><p class="text-muted mb-1" style="font-size:12px;">Low Stock (&le;10)</p><h2 class="fw-bold mb-0 text-danger"><?php echo number_format($low_stock); ?></h2></div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search/Filter -->
    <div class="table-card mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <div style="position:relative;flex:1;min-width:180px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..."
                       style="width:100%;padding:9px 14px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;">
            </div>
            <select name="category" class="form-select" style="width:180px;font-size:13px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php if($category_filter===$cat) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($cat); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary px-4">Filter</button>
            <?php if ($search || $category_filter): ?>
                <a href="products.php" class="btn btn-outline-secondary">Clear</a>
                <span class="align-self-center text-muted small"><?php echo count($products); ?> found</span>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">All Products</h5>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
                <input type="text" id="prodSearch" class="tbl-search" placeholder="Filter table...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover" id="productsTable">
                <thead><tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                <?php if ($products): foreach ($products as $p): ?>
                <tr data-id="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>"
                    data-category="<?php echo htmlspecialchars($p['category'] ?? ''); ?>"
                    data-price="<?php echo $p['price']; ?>" data-stock="<?php echo $p['stock']; ?>"
                    data-desc="<?php echo htmlspecialchars($p['description'] ?? ''); ?>"
                    data-status="<?php echo $p['status']; ?>">
                    <td style="color:#94a3b8;"><?php echo $p['id']; ?></td>
                    <td>
                        <strong style="font-size:13.5px;"><?php echo htmlspecialchars($p['name']); ?></strong>
                        <?php if (!empty($p['description'])): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($p['description'], 0, 50)); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12.5px;color:#64748b;"><?php echo htmlspecialchars($p['category'] ?? 'Uncategorized'); ?></td>
                    <td><strong>TZS <?php echo number_format($p['price'], 0); ?></strong></td>
                    <td>
                        <?php $sl = $p['stock'] > 50 ? 'sb-delivered' : ($p['stock'] > 10 ? 'sb-pending' : 'sb-cancelled'); ?>
                        <span class="sbadge <?php echo $sl; ?>">
                            <i class="fas fa-cube me-1"></i><?php echo number_format($p['stock']); ?>
                        </span>
                    </td>
                    <td><span class="sbadge sb-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button onclick="editProd(this.closest('tr'))" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></button>
                            <button onclick="delProd(<?php echo $p['id']; ?>)" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted"><?php echo ($search || $category_filter) ? 'No products match your search.' : 'No products yet.'; ?></h5>
                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addModal">Add First Product</button>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST"><input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-600">Product Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-600">Category</label><input type="text" name="category" class="form-control" placeholder="e.g. Clothing, Electronics"></div>
                    <div class="col-md-6"><label class="form-label fw-600">Price (TZS) *</label><input type="number" name="price" class="form-control" step="0.01" required></div>
                    <div class="col-md-6"><label class="form-label fw-600">Stock Qty *</label><input type="number" name="stock" class="form-control" value="0" required></div>
                    <div class="col-12"><label class="form-label fw-600">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <div class="col-12"><label class="form-label fw-600">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Product</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="eId">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-600">Product Name *</label><input type="text" name="name" id="eName" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-600">Category</label><input type="text" name="category" id="eCat" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label fw-600">Price (TZS) *</label><input type="number" name="price" id="ePrice" class="form-control" step="0.01" required></div>
                    <div class="col-md-6"><label class="form-label fw-600">Stock Qty *</label><input type="number" name="stock" id="eStock" class="form-control" required></div>
                    <div class="col-12"><label class="form-label fw-600">Description</label><textarea name="description" id="eDesc" class="form-control" rows="3"></textarea></div>
                    <div class="col-12"><label class="form-label fw-600">Status</label><select name="status" id="eStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function delProd(id) {
    if (confirm('Delete this product? This action cannot be undone.')) {
        window.location.href = 'products.php?delete=' + id;
    }
}
function editProd(row) {
    document.getElementById('eId').value    = row.dataset.id;
    document.getElementById('eName').value  = row.dataset.name;
    document.getElementById('eCat').value   = row.dataset.category;
    document.getElementById('ePrice').value = row.dataset.price;
    document.getElementById('eStock').value = row.dataset.stock;
    document.getElementById('eDesc').value  = row.dataset.desc;
    document.getElementById('eStatus').value= row.dataset.status;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
document.getElementById('prodSearch').addEventListener('keyup', function() {
    const v = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(v) ? '' : 'none';
    });
});
</script>
</body>
</html>
