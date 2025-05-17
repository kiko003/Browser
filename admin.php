<?php
session_start();
include "db.php";

// Ensure the user is logged in *and* is an admin.
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true
) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

// ------------------------------
// Process Account Deletion
// ------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);
    if ($deleteId == $_SESSION["user_id"]) {
        $error = "You cannot delete yourself.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "User deleted successfully.";
        } else {
            $error = "Failed to delete user.";
        }
        $stmt->close();
    }
}

// ------------------------------
// Process User Access Approval or Denial
// ------------------------------
if (
    isset($_GET['action']) &&
    ( $_GET['action'] == 'approve' || $_GET['action'] == 'deny') &&
    isset($_GET['id'])
) {
    $userId = intval($_GET['id']);
    $status = ($_GET['action'] == 'approve') ? 1 : -1;
    
    $stmt = $conn->prepare("UPDATE users SET approved = ? WHERE id = ?");
    if ($stmt === false) {
        $error = "Error preparing statement: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $status, $userId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $msgText = ($_GET['action'] == 'approve') ? "approved" : "denied";
            $message = "User " . $msgText . " successfully.";
        } else {
            $error = "Failed to " . $_GET['action'] . " user.";
        }
        $stmt->close();
    }
}

// ------------------------------
// Process Virtual Machine Termination
// ------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'terminate' && isset($_GET['user_id'])) {
    $vmUserId = intval($_GET['user_id']);
    // Update the VM's status to 'terminated' (adjust query/table name as needed)
    $stmt = $conn->prepare("UPDATE virtual_machines SET status = 'terminated' WHERE user_id = ?");
    if (!$stmt) {
        $error = "Error preparing VM termination statement: " . $conn->error;
    } else {
        $stmt->bind_param("i", $vmUserId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "Virtual Machine terminated successfully for user " . $vmUserId . ".";
        } else {
            $error = "Failed to terminate VM.";
        }
        $stmt->close();
    }
}

// ------------------------------
// Retrieve the list of users (with their approved status)
// ------------------------------
$sql = "SELECT id, username, approved FROM users";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Manage Users</title>
  <link rel="stylesheet" href="styles.css">
  <style>
      table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
      }
      table, th, td {
          border: 1px solid #555;
      }
      th, td {
          padding: 10px;
          text-align: left;
      }
      th {
          background-color: #222;
      }
      td {
          background-color: #111;
      }
      /* Action button styling */
      a.action-btn {
          margin-right: 5px;
          padding: 5px 10px;
          border-radius: 5px;
          text-decoration: none;
          color: #fff;
      }
      a.delete-btn { background-color: #dc3545; }
      a.terminate-btn { background-color: #ff4500; }
      a.approve-btn { background-color: #28a745; }
      a.deny-btn { background-color: #dc3545; }
  </style>
</head>
<body>
  <!-- Auto-Hiding Header -->
  <header id="topHeader">
      <span>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
      <nav>
         <a href="index.php">Main Page</a>
         <!-- Admin Panel link (only displayed for admins) -->
         <a href="admin.php">Admin Panel</a>
         <a href="logout.php">Logout</a>
      </nav>
  </header>
  <main>
      <?php
         if (!empty($message)) {
             echo "<p class='message'>" . htmlspecialchars($message) . "</p>";
         }
         if (!empty($error)) {
             echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
         }
      ?>
      <table>
          <thead>
              <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Status</th>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
              <?php
              if ($result && $result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                      // Display textual status based on the 'approved' value:
                      // 1 = Approved, -1 = Denied, 0 = Pending.
                      $statusText = ($row['approved'] == 1) ? "Approved" : (($row['approved'] == -1) ? "Denied" : "Pending");
                      echo "<td>" . $statusText . "</td>";
                      echo "<td>";
                      // Delete user link (prevent deletion of self)
                      if ($row['id'] != $_SESSION["user_id"]) {
                          echo "<a class='action-btn delete-btn' href='admin.php?action=delete&id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this user?\");'>Delete</a> ";
                      } else {
                          echo "Delete: N/A ";
                      }
                      // VM Termination link (check if a VM session exists for this user)
                      $checkSql = "SELECT session_data FROM hyperbeam_sessions WHERE user_id = " . $row['id'] . " LIMIT 1";
                      $checkResult = $conn->query($checkSql);
                      if ($checkResult && $checkResult->num_rows > 0) {
                          echo "<a class='action-btn terminate-btn' href='admin.php?action=terminate&user_id=" . $row['id'] . "' onclick='return confirm(\"Terminate the VM session for " . htmlspecialchars($row['username']) . "?\");'>Terminate VM</a> ";
                      } else {
                          echo "No VM ";
                      }
                      // Approve/Deny actions (skip these actions for self)
                      if ($row['id'] != $_SESSION["user_id"]) {
                          if ($row['approved'] == 0) {
                              echo "<a class='action-btn approve-btn' href='admin.php?action=approve&id=" . $row['id'] . "' onclick='return confirm(\"Approve " . htmlspecialchars($row['username']) . "?\");'>Approve</a> ";
                              echo "<a class='action-btn deny-btn' href='admin.php?action=deny&id=" . $row['id'] . "' onclick='return confirm(\"Deny " . htmlspecialchars($row['username']) . "?\");'>Deny</a>";
                          } elseif ($row['approved'] == 1) {
                              echo "<span style='color: #28a745; font-weight: bold;'>Approved</span> ";
                              echo "<a class='action-btn deny-btn' href='admin.php?action=deny&id=" . $row['id'] . "' onclick='return confirm(\"Deny " . htmlspecialchars($row['username']) . "?\");'>Deny</a>";
                          } elseif ($row['approved'] == -1) {
                              echo "<span style='color: #dc3545; font-weight: bold;'>Denied</span> ";
                              echo "<a class='action-btn approve-btn' href='admin.php?action=approve&id=" . $row['id'] . "' onclick='return confirm(\"Approve " . htmlspecialchars($row['username']) . "?\");'>Approve</a>";
                          }
                      }
                      echo "</td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='4'>No users found.</td></tr>";
              }
              ?>
          </tbody>
      </table>
  </main>
</body>
</html>