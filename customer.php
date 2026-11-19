<?php
session_start();
include 'connect.php';

$order_msg = "";
$order_err = "";

if (isset($_SESSION['order_success'])) {
    $order_msg = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}
if (isset($_SESSION['order_error'])) {
    $order_err = $_SESSION['order_error'];
    unset($_SESSION['order_error']);
}

// Handle Order Placement
if (isset($_POST['place_order'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $customer_id = intval($_SESSION['user_id']);
    $product_id  = intval($_POST['product_id']);
    $qty_ordered = trim($_POST['quantity_ordered']);
    $address     = trim($_POST['delivery_address']);

    if ($product_id > 0 && $qty_ordered !== "" && $address !== "") {
        $stmt = mysqli_prepare($conn, "INSERT INTO orders (customer_id, product_id, quantity_ordered, delivery_address) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $customer_id, $product_id, $qty_ordered, $address);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['order_success'] = "Order placed successfully! The farmer will contact you shortly.";
        } else {
            $_SESSION['order_error'] = "Error placing order: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['order_error'] = "Please choose a product, enter a quantity, and provide a delivery address.";
    }

    header("Location: customer.php#order-section");
    exit();
}

// Search and Filter
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$search_esc   = mysqli_real_escape_string($conn, $search);
$category_esc = mysqli_real_escape_string($conn, $category);

$query = "SELECT p.*, u.full_name, u.phone
          FROM products p
          JOIN users u ON p.farmer_id = u.user_id
          WHERE 1=1";

if ($search_esc !== '') {
    $query .= " AND (p.product_name LIKE '%{$search_esc}%' OR u.full_name LIKE '%{$search_esc}%' OR p.category LIKE '%{$search_esc}%')";
}
if ($category_esc !== '' && $category_esc !== 'All') {
    $query .= " AND p.category = '{$category_esc}'";
}
$query .= " ORDER BY p.product_id DESC";
$res = mysqli_query($conn, $query);

// Product list for select dropdown
$products_for_select = [];
$plist = mysqli_query($conn, "SELECT product_id, product_name, price, quantity FROM products ORDER BY product_name ASC");
if ($plist) {
    while ($p = mysqli_fetch_assoc($plist)) {
        $products_for_select[] = $p;
    }
}

// Orders placed by logged-in user
$my_orders = [];
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $orders_sql = "SELECT o.*, p.product_name, p.price, u.full_name AS farmer_name
                   FROM orders o
                   JOIN products p ON o.product_id = p.product_id
                   LEFT JOIN users u ON p.farmer_id = u.user_id
                   WHERE o.customer_id = {$uid}
                   ORDER BY o.order_id DESC";
    $ores = mysqli_query($conn, $orders_sql);
    if ($ores) {
        while ($row = mysqli_fetch_assoc($ores)) {
            $my_orders[] = $row;
        }
    }
}

$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AGRI-MIA | Marketplace</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-main {
            display: block !important;
            width: 100% !important;
            max-width: 1180px !important;
            margin: 20px auto !important;
            padding: 0 16px !important;
            box-sizing: border-box !important;
        }
        .page-main article,
        .page-main section,
        .page-main form {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            flex: none !important;
            box-sizing: border-box !important;
        }
        .msg-success {
            background: #E8F5E9;
            padding: 12px 16px;
            font-weight: bold;
            color: #1B5E20;
            border-radius: 6px;
            border: 1px solid #66BB6A;
            margin: 12px 0;
        }
        .msg-error {
            background: #FFEBEE;
            padding: 12px 16px;
            font-weight: bold;
            color: #B71C1C;
            border-radius: 6px;
            border: 1px solid #EF9A9A;
            margin: 12px 0;
        }
        .filter-row {
            display: grid !important;
            grid-template-columns: 2fr 1fr auto;
            gap: 12px;
            margin: 15px 0 22px 0;
            padding: 14px;
            background: #E8F5E9;
            border: 1px solid #A5D6A7;
            border-radius: 8px;
        }
        .filter-row input, .filter-row select, .filter-row button {
            width: 100%;
            margin: 0 !important;
            padding: 10px 12px;
            box-sizing: border-box;
        }
        .three-col-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 16px;
        }
        .product-card {
            background: #ffffff;
            border: 1px solid #C8E6C9;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .product-body {
            padding: 14px;
            flex-grow: 1;
        }
        .product-cat {
            font-size: 12px;
            text-transform: uppercase;
            color: #2E7D32;
            font-weight: bold;
        }
        .product-title {
            margin: 6px 0;
            font-size: 18px;
            color: #1B5E20;
        }
        .product-price {
            font-size: 17px;
            font-weight: bold;
            margin: 6px 0;
        }
        .product-meta {
            font-size: 14px;
            margin: 4px 0;
        }
        .order-card-btn {
            width: 100%;
            padding: 12px;
            border: none;
            background: #1B5E20;
            color: #ffffff;
            font-weight: bold;
            cursor: pointer;
        }
        .order-card-btn:hover {
            background: #2E7D32;
        }
        .bottom-block {
            background: #ffffff;
            border: 1px solid #C8E6C9;
            border-radius: 10px;
            padding: 22px;
            margin-top: 32px;
        }
        .form-columns {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 16px;
        }
        .form-col label {
            display: block;
            font-weight: bold;
            color: #1B5E20;
            margin-bottom: 6px;
        }
        .form-col input, .form-col select {
            width: 100%;
            padding: 10px;
            border: 1px solid #A5D6A7;
            border-radius: 6px;
            box-sizing: border-box;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .orders-table th, .orders-table td {
            border: 1px solid #C8E6C9;
            padding: 10px 12px;
            text-align: left;
        }
        .orders-table th {
            background: #E8F5E9;
            color: #1B5E20;
        }
        @media (max-width: 850px) {
            .three-col-grid, .form-columns, .filter-row {
                grid-template-columns: 1fr;
            }
        }
		
		.show {
    height: 260px;
    width: 260px;
    border-radius: 30px;
    margin-right: 60px;
    margin-top: 60px;
    object-fit: cover;
    border: 2px solid #c8e6c9;
    box-shadow: 0 4px 12px rgba(27, 94, 32, 0.12);
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    cursor: pointer;
	}

	.show:hover {
		transform: scale(1.05);
		border-color: #2e7d32;
		box-shadow: 0 10px 22px rgba(27, 94, 32, 0.24);
	}
    </style>
</head>
<body>
    <header>
        <img src="images/TM.jpeg" alt="Logo" style="width:280px; height:105px; border-radius:20px;">
        <h1>AGRI-MIA MARKETPLACE</h1>
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

    <main class="page-main">
        <?php if ($order_msg !== ""): ?>
            <div class="msg-success"><?php echo htmlspecialchars($order_msg); ?></div>
        <?php endif; ?>
        <?php if ($order_err !== ""): ?>
            <div class="msg-error"><?php echo htmlspecialchars($order_err); ?></div>
        <?php endif; ?>

        <article>
            <h2>Available Farm Products</h2>

            <form method="GET" action="customer.php" class="filter-row">
                <input type="text" name="search" placeholder="Search product, category, or farmer..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="All">All Categories</option>
                    <option value="Vegetables" <?php if($category === 'Vegetables') echo 'selected'; ?>>Vegetables</option>
                    <option value="Fruits" <?php if($category === 'Fruits') echo 'selected'; ?>>Fruits</option>
                    <option value="Grains & Cereals" <?php if($category === 'Grains & Cereals') echo 'selected'; ?>>Grains & Cereals</option>
                    <option value="Dairy & Poultry" <?php if($category === 'Dairy & Poultry') echo 'selected'; ?>>Dairy & Poultry</option>
                </select>
                <button type="submit">Filter</button>
            </form>

            <div class="three-col-grid">
                <?php
                if ($res && mysqli_num_rows($res) > 0) {
                    while ($item = mysqli_fetch_assoc($res)) {
                        $img     = (!empty($item['image_name'])) ? "images/" . htmlspecialchars(basename($item['image_name'])) : "images/agr1.png";
                        $pid     = intval($item['product_id']);
                        $pname   = htmlspecialchars($item['product_name']);
                        $pcat    = htmlspecialchars($item['category']);
                        $pprice  = htmlspecialchars($item['price']);
                        $pqty    = htmlspecialchars($item['quantity']);
                        $pfarmer = htmlspecialchars($item['full_name']);
                        ?>
                        <div class="product-card">
                            <img src="<?php echo $img; ?>" alt="<?php echo $pname; ?>">
                            <div class="product-body">
                                <div class="product-cat"><?php echo $pcat; ?></div>
                                <h3 class="product-title"><?php echo $pname; ?></h3>
                                <div class="product-price">UGX <?php echo $pprice; ?></div>
                                <div class="product-meta"><strong>Available:</strong> <?php echo $pqty; ?></div>
                                <div class="product-meta"><strong>Farmer:</strong> <?php echo $pfarmer; ?></div>
                            </div>
                            <button type="button" class="order-card-btn" onclick="selectProductForOrder(<?php echo $pid; ?>, '<?php echo addslashes($item['product_name']); ?>', '<?php echo addslashes($item['price']); ?>')">
                                Order This Item
                            </button>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p style='grid-column: 1 / -1; text-align: center; padding: 20px;'>No products found matching your search.</p>";
                }
                ?>
            </div>
        <article style="text-align:center;">
				<img class="show" src="images/im1.jpg">
				<img class="show" src="images/sug.jpg">
				<img class="show" src="images/ban.jpg">
				<img class="show" src="images/frut.jpg">
				<img class="show" src="images/pac.jpg">
				<img class="show" src="images/cas.jpg">
				<img class="show" src="images/eg.jpg">
				<img class="show" src="images/fruty.jpg">
				<img class="show" src="images/tom.jpg">
				<img class="show" src="images/avo.jpg">
				<img class="show" src="images/cab.jpg">
				<img class="show" src="images/mat.jpg">
				<img class="show" src="images/onions.jpg">
				<img class="show" src="images/pumpkin.jpg">
				<img class="show" src="images/maize.jpg">
		</article>
		</article>
        <section id="order-section" class="bottom-block">
            <h2>Place Your Order</h2>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <p style="color: #1B5E20; font-weight: bold;">
                    Please <a href="login.php">login</a> or <a href="register.php">register</a> to submit an order after selecting a product.
                </p>
            <?php else: ?>
                <div id="selectedProductLabel" style="background:#E8F5E9; padding:10px 14px; border-radius:6px; margin-bottom:14px; font-weight:bold; color:#1B5E20;">
                    Selected Product: Choose from the dropdown or click "Order This Item" above.
                </div>

                <form method="POST" action="customer.php">
                    <div class="form-columns">
                        <div class="form-col">
                            <label for="product_select">Selected Product</label>
                            <select name="product_id" id="product_select" onchange="updateSelectedLabel()" required>
                                <option value="">-- Select a Product --</option>
                                <?php foreach ($products_for_select as $p): ?>
                                    <option value="<?php echo intval($p['product_id']); ?>">
                                        <?php echo htmlspecialchars($p['product_name']); ?> - UGX <?php echo htmlspecialchars($p['price']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-col">
                            <label for="quantity_ordered">Quantity Needed</label>
                            <input type="text" id="quantity_ordered" name="quantity_ordered" placeholder="e.g., 5 kg, 2 crates" required>
                        </div>

                        <div class="form-col">
                            <label for="delivery_address">Delivery Address</label>
                            <input type="text" id="delivery_address" name="delivery_address" placeholder="Town, street, or landmark" required>
                        </div>
                    </div>

                    <button type="submit" name="place_order">SUBMIT ORDER</button>
                </form>
            <?php endif; ?>
        </section>

        <?php if (isset($_SESSION['user_id'])): ?>
            <section id="my-orders" class="bottom-block">
                <h2>Orders Placed</h2>
                <?php if (count($my_orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Product</th>
                                <th>Farmer</th>
                                <th>Quantity Ordered</th>
                                <th>Unit Price (UGX)</th>
                                <th>Delivery Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_orders as $ord): ?>
                                <tr>
                                    <td><?php echo intval($ord['order_id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($ord['product_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ord['farmer_name'] ?? 'Local Farmer'); ?></td>
                                    <td><?php echo htmlspecialchars($ord['quantity_ordered']); ?></td>
                                    <td><?php echo htmlspecialchars($ord['price']); ?></td>
                                    <td><?php echo htmlspecialchars($ord['delivery_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>You have not placed any orders yet.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p><b>Email: agrimia@gmail.com | Tel: +256 7758968</b></p>
    </footer>

    <script>
        function selectProductForOrder(productId, productName, price) {
            const selectEl = document.getElementById('product_select');
            const labelEl = document.getElementById('selectedProductLabel');
            const qtyInput = document.getElementById('quantity_ordered');
            const orderSection = document.getElementById('order-section');

            if (selectEl) {
                selectEl.value = String(productId);
            }
            if (labelEl) {
                labelEl.textContent = "Selected Product: " + productName + " — UGX " + price;
            }
            if (orderSection) {
                orderSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (qtyInput) {
                setTimeout(() => qtyInput.focus(), 400);
            }
        }

        function updateSelectedLabel() {
            const selectEl = document.getElementById('product_select');
            const labelEl = document.getElementById('selectedProductLabel');
            if (!selectEl || !labelEl) return;

            const chosen = selectEl.options[selectEl.selectedIndex];
            if (chosen && chosen.value !== "") {
                labelEl.textContent = "Selected Product: " + chosen.text;
            } else {
                labelEl.textContent = 'Selected Product: Choose from the dropdown or click "Order This Item" above.';
            }
        }
    </script>
</body>
</html>