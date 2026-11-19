<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    header("Location: login.php");
    exit();
}

$farmer_id = intval($_SESSION['user_id']);
$msg = "";
$err = "";

// Add Product
if (isset($_POST['add_product'])) {
    $pname    = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $price    = floatval($_POST['price']);
    $quantity = trim($_POST['quantity']);
    $img_name = trim($_POST['image_name']);
    $img_val  = ($img_name !== "") ? $img_name : null;

    if ($pname !== "" && $category !== "" && $price > 0 && $quantity !== "") {
        $stmt = mysqli_prepare($conn, "INSERT INTO products (farmer_id, product_name, category, price, quantity, image_name) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issdss", $farmer_id, $pname, $category, $price, $quantity, $img_val);
            if (mysqli_stmt_execute($stmt)) {
                $msg = "Product listed successfully!";
            } else {
                $err = "Could not save product: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $err = "Database query error: " . mysqli_error($conn);
        }
    } else {
        $err = "Please enter a valid product name, category, price greater than 0, and quantity.";
    }
}

// Delete Product
if (isset($_GET['delete'])) {
    $pid = intval($_GET['delete']);
    $del = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ? AND farmer_id = ?");
    if ($del) {
        mysqli_stmt_bind_param($del, "ii", $pid, $farmer_id);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
    }
    header("Location: farm.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AGRI-MIA | Farmer Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>FARMER DASHBOARD — <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        <nav>
            <a href="index.html">HOME</a>
            <a href="about.html">ABOUT US</a>
            <a href="contact.html">CONTACT US</a>
            <a href="customer.php">MARKETPLACE</a>
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

    <main>
        <section>
            <h2>Post New Harvest</h2>
            <?php if ($msg !== ""): ?>
                <p style="color:#1B5E20; font-weight:bold;"><?php echo htmlspecialchars($msg); ?></p>
            <?php endif; ?>
            <?php if ($err !== ""): ?>
                <p style="color:#B71C1C; font-weight:bold;"><?php echo htmlspecialchars($err); ?></p>
            <?php endif; ?>

            <form method="POST" action="farm.php">
                <label>Product Name (e.g., Fresh Carrots, Matooke)</label>
                <input type="text" name="product_name" required>

                <label>Category</label>
                <select name="category">
                    <option value="Vegetables">Vegetables</option>
                    <option value="Fruits">Fruits</option>
                    <option value="Grains & Cereals">Grains & Cereals</option>
                    <option value="Dairy & Poultry">Dairy & Poultry</option>
                </select>

                <label>Price (UGX)</label>
                <input type="number" name="price" min="1" step="0.01" required>

                <label>Available Quantity (e.g., 50 kg, 20 bunches)</label>
                <input type="text" name="quantity" required>

                <label>Image Filename in images/ folder (optional, e.g., agr1.png)</label>
                <input type="text" name="image_name" placeholder="e.g. agr1.png">

                <button type="submit" name="add_product">POST PRODUCT</button>
            </form>
        </section>

        <article>
            <h2>My Listed Produce</h2>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price (UGX)</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
                <?php
                $products = mysqli_query($conn, "SELECT * FROM products WHERE farmer_id = {$farmer_id} ORDER BY product_id DESC");
                if ($products) {
                    while ($row = mysqli_fetch_assoc($products)) {
                        $pid   = intval($row['product_id']);
                        $pname = htmlspecialchars($row['product_name']);
                        $pcat  = htmlspecialchars($row['category']);
                        $pprc  = htmlspecialchars($row['price']);
                        $pqty  = htmlspecialchars($row['quantity']);
                        echo "<tr>
                                <td>{$pname}</td>
                                <td>{$pcat}</td>
                                <td>{$pprc}</td>
                                <td>{$pqty}</td>
                                <td><a href='farm.php?delete={$pid}' style='color:red; font-weight:bold;'>Remove</a></td>
                              </tr>";
                    }
                }
                ?>
            </table>

            <h2 style="margin-top:35px;">Customer Orders for My Produce</h2>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Qty Ordered</th>
                    <th>Delivery Address</th>
                </tr>
                <?php
                $orders_sql = "SELECT o.order_id, p.product_name, u.full_name, u.phone, o.quantity_ordered, o.delivery_address
                               FROM orders o
                               JOIN products p ON o.product_id = p.product_id
                               JOIN users u ON o.customer_id = u.user_id
                               WHERE p.farmer_id = {$farmer_id}
                               ORDER BY o.order_id DESC";
                $orders = mysqli_query($conn, $orders_sql);
                if ($orders) {
                    while ($ord = mysqli_fetch_assoc($orders)) {
                        echo "<tr>
                                <td>#" . intval($ord['order_id']) . "</td>
                                <td>" . htmlspecialchars($ord['product_name']) . "</td>
                                <td>" . htmlspecialchars($ord['full_name']) . "</td>
                                <td>" . htmlspecialchars($ord['phone']) . "</td>
                                <td>" . htmlspecialchars($ord['quantity_ordered']) . "</td>
                                <td>" . htmlspecialchars($ord['delivery_address']) . "</td>
                              </tr>";
                    }
                }
                ?>
            </table>
        </article>
    </main>

    <footer>
        <p><b>Email: agrimia@gmail.com | Tel: +256 7758968</b></p>
    </footer>
</body>
</html>