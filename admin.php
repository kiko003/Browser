<link rel="stylesheet" href="styles.css?v=2">
<?php
session_start();
include "db.php";

// Ensure the user is logged in *and* is an admin
if (
    !isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true ||
    !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true
) {
    header("Location: login.php");
    exit;
}

$message = isset($_GET['message']) ? $_GET['message'] : "";
$error = isset($_GET['error']) ? $_GET['error'] : "";

// Process Account Deletion
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

// Process User Access Approval, Denial, or Toggle
if (
    isset($_GET['action']) &&
    (
        $_GET['action'] == 'approve' ||
        $_GET['action'] == 'deny' ||
        $_GET['action'] == 'toggle_approve'
    ) &&
    isset($_GET['id'])
) {
    $userId = intval($_GET['id']);
    // Toggle approve/deny with a single button
    if ($_GET['action'] === 'toggle_approve') {
        // Get current status
        $stmt = $conn->prepare("SELECT approved FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($approved);
        $stmt->fetch();
        $stmt->close();
        // Toggle: if approved, set to denied (-1); if denied or anything else, set to approved (1)
        $newStatus = ($approved == 1) ? -1 : 1;
        $stmt = $conn->prepare("UPDATE users SET approved=? WHERE id=?");
        $stmt->bind_param("ii", $newStatus, $userId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "User status changed successfully.";
        } else {
            $error = "Failed to change user status.";
        }
        $stmt->close();
    } else {
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
                $error = "Failed to update user approval status.";
            }
            $stmt->close();
        }
    }
}

// Handle VM Block/Unblock action
if (isset($_GET['action']) && ($_GET['action'] == 'block_vm' || $_GET['action'] == 'unblock_vm') && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $blockValue = ($_GET['action'] == 'block_vm') ? 1 : 0;
    $stmt = $conn->prepare("UPDATE users SET vm_blocked = ? WHERE id = ?");
    $stmt->bind_param("ii", $blockValue, $userId);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $msgText = ($blockValue) ? "blocked from" : "unblocked for";
        $message = "User successfully $msgText requesting new virtual machines.";
    } else {
        $error = "Failed to update VM block status.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel - Manage Users</title>
  <link rel="stylesheet" href="styles.css?v=10">
</head>
<body>
  <header id="topHeader">
      <span>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
      <nav>
         <a href="index.php">Main Page</a>
         <?php if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) { ?>
            <a href="admin.php">Admin Panel</a>
         <?php } ?>
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
      <div class="user-list-container">
        <h2>User List</h2>
        <table class="user-list-table">
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Approved</th>
            <th>Admin</th>
            <th>VM Blocked</th>
            <th>Actions</th>
          </tr>
          <?php
          $userSessions = [];
          $sessResult = $conn->query("SELECT user_id FROM hyperbeam_sessions");
          while ($sessRow = $sessResult->fetch_assoc()) {
              $userSessions[$sessRow['user_id']] = true;
          }

          $result = $conn->query("SELECT id, username, approved, is_admin, vm_blocked FROM users");
          while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>" . htmlspecialchars($row['id']) . "</td>";
              echo "<td>" . htmlspecialchars($row['username']) . "</td>";
              echo "<td>" . ($row['approved'] == 1 ? "Yes" : "No") . "</td>";
              echo "<td>" . ($row['is_admin'] == 1 ? "Yes" : "No") . "</td>";
              echo "<td>" . ($row['vm_blocked'] == 1 ? "Yes" : "No") . "</td>";
              echo "<td>";
              if ($row['id'] != $_SESSION["user_id"]) {
                  echo "<div class='actions-row'>";
                  
                  // Toggle Approve/Deny (Brown, broken glass icon)
                  $toggleText = ($row['approved'] == 1) ? "Deny" : "Approve";
                  $toggleTitle = ($row['approved'] == 1) ? "Deny this user" : "Approve this user";
                  echo "<a class='action-btn disable-btn' href='admin.php?action=toggle_approve&id=" . $row['id'] . "' title='" . $toggleTitle . "' onclick='return confirm(\"Are you sure you want to $toggleText this user?\");'>
                          <span class='btn-icon'>
                              <svg width='20' height='20' viewBox='0 0 20 20'>
                                <g>
                                  <path d='M4 17L16 3' stroke='#fff' stroke-width='1.6' stroke-linecap='round'/>
                                  <path d='M10 3l2 4 5 1.5-3.5 3 .5 4.5-4-2-4 2 .5-4.5-3.5-3L8 7l2-4z' stroke='#fff' stroke-width='1.2' fill='none'/>
                                </g>
                              </svg>
                          </span>
                          <span class='btn-text'>$toggleText</span>
                      </a>";

                  // DELETE BUTTON (Red)
                  echo "<a class='action-btn delete-btn' href='admin.php?action=delete&id=" . $row['id'] . "' onclick='return confirm(\"Delete user " . htmlspecialchars($row['username']) . "?\");'>
                      <span class='btn-icon'>
                          <svg width='20' height='20' viewBox='0 0 20 20'>
                            <path d='M7 8v6m3-6v6m3-10V4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v2M4 6h12v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Z'
                              stroke='#fff' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/>
                          </svg>
                      </span>
                      <span class='btn-text'>Delete</span>
                  </a>";

                  // Terminate VM button (Orange)
                  if (isset($userSessions[$row['id']])) {
                      echo "<a class='action-btn terminate-btn' href='terminate_vm.php?user_id=" . $row['id'] . "' onclick='return confirm(\"Terminate the VM session for " . htmlspecialchars($row['username']) . "?\");'>
                          <span class='btn-icon'>
                              <svg width='20' height='20' viewBox='0 0 20 20'>
                                <path d='M14 6L6 14M6 6l8 8' stroke='#fff' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/>
                              </svg>
                          </span>
                          <span class='btn-text'>Terminate VM</span>
                      </a>";
                  } else {
                      echo "<span class='action-btn terminate-btn disabled'>
                          <span class='btn-icon'>
                              <svg width='20' height='20' viewBox='0 0 20 20'>
                                <path d='M14 6L6 14M6 6l8 8' stroke='#fff' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/>
                              </svg>
                          </span>
                          <span class='btn-text'>Terminate VM</span>
                      </span>";
                  }

                  // Block/Unblock VM (Purple)
                  if ($row['vm_blocked'] == 0) {
                      echo "<a class='action-btn block-vm-btn' href='admin.php?action=block_vm&id=" . $row['id'] . "' onclick='return confirm(\"Block this user from requesting new VMs?\");'>
                          <span class='btn-icon'>
                              <svg width='20' height='20' viewBox='0 0 20 20'>
                                <circle cx='10' cy='10' r='7' stroke='#fff' stroke-width='1.5' fill='none'/>
                                <line x1='6' y1='14' x2='14' y2='6' stroke='#fff' stroke-width='1.5'/>
                              </svg>
                          </span>
                          <span class='btn-text'>Block VM</span>
                      </a>";
                  } else {
                      echo "<a class='action-btn approve-btn' href='admin.php?action=unblock_vm&id=" . $row['id'] . "' onclick='return confirm(\"Unblock this user for VM requests?\");'>Unblock VM</a>";
                  }
                  echo "</div>";
              } else {
                  echo "N/A";
              }
              echo "</td>";
              echo "</tr>";
          }
          ?>
        </table>
      </div>
  </main>
</body>
</html>