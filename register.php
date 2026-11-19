<?php
session_start();
include 'connect.php';

$msg = "";
$err = "";

if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $role      = ($_POST['role'] === 'farmer') ? 'farmer' : 'customer';

    if ($full_name === "" || $phone === "" || $email === "" || $password === "") {
        $err = "Please fill in all required fields.";
    } else {
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $err = "That email is already registered. Please login instead.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $full_name, $phone, $email, $hashed, $role);

            if (mysqli_stmt_execute($stmt)) {
                $msg = "Account created successfully! You can now log in.";
            } else {
                $err = "Registration failed: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AGRI-MIA | Register</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-box {
            max-width: 480px;
            margin: 30px auto;
            background: #ffffff;
            padding: 24px;
            border: 1px solid #C8E6C9;
            border-radius: 10px;
        }
        .auth-box label {
            display: block;
            font-weight: bold;
            color: #1B5E20;
            margin-top: 12px;
            margin-bottom: 5px;
        }
        .auth-box input, .auth-box select, .auth-box button {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
        .auth-box button {
            margin-top: 18px;
            background: #1B5E20;
            color: #ffffff;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        .notice-ok { background: #E8F5E9; color: #1B5E20; padding: 10px; border-radius: 6px; font-weight: bold; }
        .notice-err { background: #FFEBEE; color: #B71C1C; padding: 10px; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <img src="images/TM.jpeg" alt="Logo" style="width:280px; height:105px; border-radius:20px;">
        <h1>CREATE AN ACCOUNT</h1>
        <nav>
            <a href="index.html">HOME</a>
            <a href="about.html">ABOUT US</a>
            <a href="contact.html">CONTACT US</a>
            <a href="customer.php">MARKETPLACE</a>
            <a href="login.php">LOGIN</a>
            <a href="register.php">REGISTER</a>
        </nav>
    </header>

    <main>
        <div class="auth-box">
            <h2>Register with AGRI-MIA</h2>
            <?php if ($msg !== ""): ?><p class="notice-ok"><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
            <?php if ($err !== ""): ?><p class="notice-err"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>

            <form method="POST" action="register.php">
                <label>Full Name</label>
                <input type="text" name="full_name" required>

                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="e.g. +256 7XX XXX XXX" required>

                <label>Email Address</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <label>Account Role</label>
                <select name="role">
                    <option value="customer">Customer (Buy Farm Produce)</option>
                    <option value="farmer">Farmer (Sell Farm Harvest)</option>
                </select>SSss

                <button type="submit" name="register">REGISTER</button>
            </form>
            <p style="margin-top:14px;">Already have an account? <a href="login.php">Login here</a>.</p>
        </div>
    </main>

    <footer>
        <p><b>Email: agrimia@gmail.com | Tel: +256 7758968</b></p>
    </footer>
</body>
</html>SS