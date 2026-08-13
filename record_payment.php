<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

$success = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Get orders available for payment
|--------------------------------------------------------------------------
*/
try {
    $orders = $conn->query("
        SELECT 
            order_id,
            product_name,
            total_price,
            customer_name,
            phone
        FROM orders
        WHERE status IN ('Processing', 'Shipped')
        ORDER BY date_created DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $error = "Imeshindikana kupata oda: " . $e->getMessage();
}

/*
|--------------------------------------------------------------------------
| Record Payment
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_id       = trim($_POST['order_id'] ?? '');
    $amount         = (float)($_POST['amount'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? '');
    $status         = trim($_POST['status'] ?? 'pending');

    /*
    | Validate input
    */
    $allowed_statuses = ['pending', 'completed', 'failed'];

    if (
        empty($order_id) ||
        $amount <= 0 ||
        empty($payment_method) ||
        !in_array($status, $allowed_statuses, true)
    ) {
        $error = "Tafadhali jaza sehemu zote zinazohitajika.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Get order information
            |--------------------------------------------------------------------------
            */
            $stmt = $conn->prepare("
                SELECT 
                    order_id,
                    customer_name,
                    phone,
                    total_price
                FROM orders
                WHERE order_id = ?
                LIMIT 1
            ");

            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                throw new Exception("Oda haijapatikana.");
            }

            /*
            |--------------------------------------------------------------------------
            | Make sure amount does not exceed order total
            |--------------------------------------------------------------------------
            */
            if ($amount > (float)$order['total_price']) {
                throw new Exception(
                    "Kiasi cha malipo hakiwezi kuzidi jumla ya oda: TZS " .
                    number_format($order['total_price'], 0)
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Insert payment
            |
            | IMPORTANT:
            | We do NOT use customer_id because your current orders table
            | does not contain customer_id.
            |--------------------------------------------------------------------------
            */
            $stmt = $conn->prepare("
                INSERT INTO payments
                    (order_id, amount, payment_method, status)
                VALUES
                    (?, ?, ?, ?)
            ");

            $stmt->execute([
                $order_id,
                $amount,
                $payment_method,
                $status
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update order payment status
            |--------------------------------------------------------------------------
            */
            

            $success = "Malipo yamehifadhiwa kikamilifu!";

            /*
            | Refresh orders after successful payment
            */
            $orders = $conn->query("
                SELECT 
                    order_id,
                    product_name,
                    total_price,
                    customer_name,
                    phone
                FROM orders
                WHERE status IN ('Processing', 'Shipped')
                ORDER BY date_created DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            $error = "Hitilafu ya database: " . $e->getMessage();

        } catch (Exception $e) {

            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sw">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rekodi Malipo - AB FASHIONSTORE</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f8fafc;
        }

        .main-container {
            max-width: 900px;
            margin: 40px auto;
        }

        .payment-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }

        .payment-header {
            background: #0d6efd;
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
        }

        .payment-header h3 {
            margin: 0;
        }

        .form-label {
            font-weight: 600;
        }

        .order-info {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }

        .btn-record {
            padding: 12px 25px;
            font-weight: 600;
        }

    </style>

</head>

<body>

<div class="container main-container">

    <div class="mb-3">

        <a href="payments.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Rudi kwenye Payments
        </a>

    </div>

    <?php if ($success): ?>

        <div class="alert alert-success alert-dismissible fade show">

            <i class="fas fa-check-circle me-2"></i>

            <?php echo htmlspecialchars($success); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="fas fa-exclamation-circle me-2"></i>

            <?php echo htmlspecialchars($error); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <div class="card payment-card">

        <div class="payment-header">

            <h3>
                <i class="fas fa-credit-card me-2"></i>
                Rekodi Malipo
            </h3>

            <small>
                Rekodi malipo ya mteja kwa oda iliyopo
            </small>

        </div>


        <div class="card-body p-4">

            <form method="POST">


                <!-- ORDER -->

                <div class="mb-4">

                    <label class="form-label">
                        Chagua Oda
                    </label>

                    <select
                        name="order_id"
                        id="order_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Chagua Oda --
                        </option>

                        <?php foreach ($orders as $order): ?>

                            <option
                                value="<?php echo htmlspecialchars($order['order_id']); ?>"
                                data-total="<?php echo htmlspecialchars($order['total_price']); ?>"
                                data-customer="<?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>"
                                data-phone="<?php echo htmlspecialchars($order['phone'] ?? ''); ?>"
                            >

                                <?php echo htmlspecialchars($order['order_id']); ?>

                                -

                                <?php echo htmlspecialchars($order['product_name']); ?>

                                -

                                TZS <?php echo number_format($order['total_price'], 0); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ORDER INFORMATION -->

                <div id="orderInfo" class="order-info">

                    <div class="row">

                        <div class="col-md-4">

                            <strong>Mteja:</strong>

                            <div id="customerName">
                                -
                            </div>

                        </div>


                        <div class="col-md-4">

                            <strong>Simu:</strong>

                            <div id="customerPhone">
                                -
                            </div>

                        </div>


                        <div class="col-md-4">

                            <strong>Jumla ya Oda:</strong>

                            <div id="orderTotal">
                                -
                            </div>

                        </div>

                    </div>

                </div>


                <!-- AMOUNT -->

                <div class="mb-4 mt-4">

                    <label class="form-label">
                        Kiasi cha Malipo (TZS)
                    </label>

                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        class="form-control"
                        min="1"
                        step="0.01"
                        placeholder="Mfano: 50000"
                        required
                    >

                    <small class="text-muted">
                        Weka kiasi ambacho mteja amelipa.
                    </small>

                </div>


                <div class="row">


                    <!-- PAYMENT METHOD -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Njia ya Malipo
                        </label>

                        <select
                            name="payment_method"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Chagua Njia --
                            </option>

                            <option value="M-Pesa">
                                M-Pesa
                            </option>

                            <option value="Airtel Money">
                                Airtel Money
                            </option>

                            <option value="Tigo Pesa">
                                Tigo Pesa
                            </option>

                            <option value="Bank Transfer">
                                Uhamisho wa Benki
                            </option>

                            <option value="Credit Card">
                                Kadi ya Mkopo
                            </option>

                            <option value="Cash">
                                Fedha Taslimu
                            </option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Hali ya Malipo
                        </label>

                        <select
                            name="status"
                            class="form-select"
                            required
                        >

                            <option value="pending">
                                Inasubiri
                            </option>

                            <option value="completed">
                                Imekamilika
                            </option>

                            <option value="failed">
                                Imeshindikana
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary btn-record"
                    >

                        <i class="fas fa-save me-2"></i>
                        Rekodi Malipo

                    </button>


                    <a
                        href="payments.php"
                        class="btn btn-light btn-record"
                    >

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

const orderSelect = document.getElementById('order_id');

const orderInfo = document.getElementById('orderInfo');

const customerName = document.getElementById('customerName');

const customerPhone = document.getElementById('customerPhone');

const orderTotal = document.getElementById('orderTotal');

const amountInput = document.getElementById('amount');


orderSelect.addEventListener('change', function () {

    const selected = this.options[this.selectedIndex];

    if (!this.value) {

        orderInfo.style.display = 'none';

        return;
    }


    const customer = selected.dataset.customer || 'N/A';

    const phone = selected.dataset.phone || 'N/A';

    const total = parseFloat(selected.dataset.total || 0);


    customerName.textContent = customer;

    customerPhone.textContent = phone;

    orderTotal.textContent =
        'TZS ' + total.toLocaleString();


    orderInfo.style.display = 'block';


    /*
    | Automatically put order total into amount field.
    | Admin can reduce it for partial payment.
    */

    amountInput.value = total;

});


</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>