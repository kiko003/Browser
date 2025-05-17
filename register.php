<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Check if the username already exists.
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo "Username already exists. Please choose a different one.";
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Hash the password and insert the new user.
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hashed_password);
    
    if ($stmt->execute()) {
        echo "Registration successful. <a href='login.php'>Click here to login</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h2>Register</h2>
  <form action="register.php" method="post">
    <input type="text" name="username" placeholder="Enter a username" required>
    <input type="password" name="password" placeholder="Enter a password" required>
    <button type="submit">Register</button>
  </form>
  <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
