<?php
session_start();
include 'connect.php';

$err = "";

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === "" || $password === "") {
        $err = "Please enter both email and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = intval($user['user_id']);
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            if ($user['role'] === 'farmer') {
                header("Location: farm.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: customer.php");
            }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AGRI-MIA | Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-box {
            max-width: 440px;
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
        .auth-box input, .auth-box button {
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
        .notice-err { background: #FFEBEE; color: #B71C1C; padding: 10px; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <img src="images/TM.jpeg" alt="Logo" style="width:280px; height:105px; border-radius:20px;">
        <h1>LOGIN TO AGRI-MIA</h1>
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
            <h2>Sign In</h2>
            <?php if ($err !== ""): ?><p class="notice-err"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>

            <form method="POST" action="login.php">
                <label>Email Address</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <button type="submit" name="login">LOGIN</button>
            </form>
            <p style="margin-top:14px;">New to AGRI-MIA? <a href="register.php">Create an account</a>.</p>
        </div>
    </main>

    <footer>
        <p><b>Email: agrimia@gmail.com | Tel: +256 7758968</b></p>
    </footer>
</body>
</html>