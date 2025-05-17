<?php
session_start();
include "db.php";

// For the purposes of this admin page, assume the admin is already authenticated.
// In production, add proper admin authentication here.

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"])) {
    $userId = intval($_POST["user_id"]);
    $stmt = $conn->prepare("UPDATE users SET approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        $message = "User approved successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Retrieve pending users (approved = 0)
$result = $conn->query("SELECT id, username FROM users WHERE approved = 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Approve Users</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <h2>Pending User Approvals</h2>
        <?php if (!empty($message)) { echo "<p>" . $message . "</p>"; } ?>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                ?>
                <div class="pending-user">
                    <span><?php echo htmlspecialchars($row["username"]); ?></span>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $row["id"]; ?>">
                        <button type="submit">Approve</button>
                    </form>
                </div>
                <?php
            }
        } else {
            echo "<p>No pending users.</p>";
        }
        ?>
    </div>
</body>
</html>