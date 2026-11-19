<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

// Delete a product if requested
if (isset($_GET['delete_product'])) {
    $pid = intval($_GET['delete_product']);
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $pid);
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Product removed.";
    }
    mysqli_stmt_close($stmt);
    header("Location: admin.php");
    exit();
}

// Delete a non-admin user if requested
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    if ($uid !== intval($_SESSION['user_id'])) {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role != 'admin'");
        mysqli_stmt_bind_param($stmt, "i", $uid);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "User account removed.";
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: admin.php");
    exit();
}

// Quick summary counts
$users_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'] ?? 0;
$products_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))['total'] ?? 0;
$orders_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders"))['total'] ?? 0;

// Fetch all users, products, and orders
$users_res = mysqli_query($conn, "SELECT user_id, full_name, phone, email, role, created_at FROM users ORDER BY user_id DESC");

$products_res = mysqli_query($conn, "SELECT p.*, u.full_name AS farmer_name
                                    FROM products p
                                    JOIN users u ON p.farmer_id = u.user_id
                                    ORDER BY p.product_id DESC");

$orders_res = mysqli_query($conn, "SELECT o.order_id, o.quantity_ordered, o.delivery_address, o.created_at,
                                  p.product_name, p.price,
                                  c.full_name AS customer_name, c.phone AS customer_phone,
                                  f.full_name AS farmer_name
                                  FROM orders o
                                  JOIN products p ON o.product_id = p.product_id
                                  JOIN users c ON o.customer_id = c.user_id
                                  LEFT JOIN users f ON p.farmer_id = f.user_id
                                  ORDER BY o.order_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AGRI-MIA | Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-main {
            display: block !important;
            width: 100% !important;
            max-width: 1200px !important;
            margin: 20px auto !important;
            padding: 0 16px !important;
            box-sizing: border-box !important;
        }
        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #E8F5E9;
            border: 1px solid #A5D6A7;
            border-left: 6px solid #1B5E20;
            border-radius: 8px;
            padding: 16px;
        }
        .stat-card h3 {
            margin: 0 0 6px 0;
            color: #1B5E20;
            font-size: 15px;
        }
        .stat-card p {
            margin: 0;
            font-size: 26px;
            font-weight: bold;
            color: #0d3010;
        }
        .admin-section {
            background: #ffffff;
            border: 1px solid #C8E6C9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 26px;
        }
        .admin-section h2 {
            margin-top: 0;
            color: #1B5E20;
            border-bottom: 2px solid #E8F5E9;
            padding-bottom: 8px;
        }
        .msg-ok {
            background: #E8F5E9;
            color: #1B5E20;
            padding: 12px 16px;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 16px;
        }
        @media (max-width: 800px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <img src="images/TM.jpeg" alt="Logo" style="width:280px; height:105px; border-radius:20px;">
        <h1>AGRI-MIA ADMIN PANEL</h1>
        <nav>
    <a href="index.html">HOME</a>[cite: 11]
    <a href="about.html">ABOUT US</a>[cite: 3]
    <a href="contact.html">CONTACT US</a>[cite: 7]
    <a href="customer.php">MARKETPLACE</a>[cite: 9]
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin.php">ADMIN PANEL</a>
        <?php elseif ($_SESSION['role'] === 'farmer'): ?>
            <a href="farm.php">FARMER DASHBOARD</a>
        <?php endif; ?>
        <a href="logout.php">LOGOUT (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</a>
    <?php else: ?>
        <a href="login.php">LOGIN</a>
        <a href="register.php">REGISTER</a>
    <?php endif; ?>
</nav>
    </header>

    <main class="admin-main">
        <?php if ($msg !== ""): ?>
            <div class="msg-ok"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Registered Users</h3>
                <p><?php echo intval($users_count); ?></p>
            </div>
            <div class="stat-card">
                <h3>Listed Products</h3>
                <p><?php echo intval($products_count); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?php echo intval($orders_count); ?></p>
            </div>
        </div>

        <section class="admin-section">
            <h2>All Customer Orders</h2>
            <table>
                <tr>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Farmer</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Qty Ordered</th>
                    <th>Price (UGX)</th>
                    <th>Delivery Address</th>
                </tr>
                <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
                    <?php while ($ord = mysqli_fetch_assoc($orders_res)): ?>
                        <tr>
                            <td>#<?php echo intval($ord['order_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($ord['product_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ord['farmer_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($ord['customer_phone']); ?></td>
                            <td><?php echo htmlspecialchars($ord['quantity_ordered']); ?></td>
                            <td><?php echo htmlspecialchars($ord['price']); ?></td>
                            <td><?php echo htmlspecialchars($ord['delivery_address']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;">No orders recorded yet.</td></tr>
                <?php endif; ?>
            </table>
        </section>

        <section class="admin-section">
            <h2>All Listed Products</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Farmer</th>
                    <th>Price (UGX)</th>
                    <th>Available</th>
                    <th>Action</th>
                </tr>
                <?php if ($products_res && mysqli_num_rows($products_res) > 0): ?>
                    <?php while ($prod = mysqli_fetch_assoc($products_res)): ?>
                        <tr>
                            <td><?php echo intval($prod['product_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($prod['product_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($prod['category']); ?></td>
                            <td><?php echo htmlspecialchars($prod['farmer_name']); ?></td>
                            <td><?php echo htmlspecialchars($prod['price']); ?></td>
                            <td><?php echo htmlspecialchars($prod['quantity']); ?></td>
                            <td>
                                <a href="admin.php?delete_product=<?php echo intval($prod['product_id']); ?>" style="color:#B71C1C; font-weight:bold;">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No products listed yet.</td></tr>
                <?php endif; ?>
            </table>
        </section>

        <section class="admin-section">
            <h2>Registered Users</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php if ($users_res && mysqli_num_rows($users_res) > 0): ?>
                    <?php while ($usr = mysqli_fetch_assoc($users_res)): ?>
                        <tr>
                            <td><?php echo intval($usr['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($usr['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($usr['phone']); ?></td>
                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                            <td><strong><?php echo htmlspecialchars($usr['role']); ?></strong></td>
                            <td>
                                <?php if ($usr['role'] !== 'admin'): ?>
                                    <a href="admin.php?delete_user=<?php echo intval($usr['user_id']); ?>" style="color:#B71C1C; font-weight:bold;">Remove</a>
                                <?php else: ?>
                                    <span>Admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No users found.</td></tr>
                <?php endif; ?>
            </table>
        </section>
    </main>

    <footer>
        <p><b>Email: agrimia@gmail.com | Tel: +256 7758968</b></p>
    </footer>
</body>
</html>