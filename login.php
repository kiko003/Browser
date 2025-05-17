<?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Select the user's id, hashed password, and approval flag
    $stmt = $conn->prepare("SELECT id, password, approved FROM users WHERE username = ?");
    if (!$stmt) {
        die("Error: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $approved);
        $stmt->fetch();

        if ($approved != 1) {
            $message = "Your account is not approved yet. Please wait for an admin to approve it.";
        } else {
            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                $_SESSION["loggedin"] = true;
                $_SESSION["user_id"]  = $id;
                $_SESSION["last_activity"] = time();
                header("Location: index.php");
                exit;
            } else {
                $message = "Invalid password.";
            }
        }
    } else {
        $message = "User not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Hyperbeam Virtual Computer</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
  <div class="login-container">
    <h2>Login</h2>
    <?php if (!empty($message)): ?>
      <p class="error"><?php echo $message; ?></p>
    <?php endif; ?>
    <form action="login.php" method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="registration.php">Register here</a></p>
  </div>
</body>
</html>