<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php";

// Proceed only if the user is logged in
if (isset($_SESSION["user_id"])) {
    $userId = $_SESSION["user_id"];

    // Retrieve the session data from the database using a prepared statement
    $stmt = $conn->prepare("SELECT session_data FROM sessions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $sessionInfo = json_decode($row["session_data"], true);

        // Verify that we have a Hyperbeam session ID
        if (isset($sessionInfo["session_id"])) {
            $session_id = $sessionInfo["session_id"];

            // Prepare Hyperbeam API details
            $api_key = "sk_live_sJofMsFmPkXhKdKg8mKnyXbEBZ5gbVo8KfYwSspVh4U";
            $cancel_url = "https://engine.hyperbeam.com/v0/session/" . $session_id;

            // Initialize cURL to send a DELETE request to cancel the Hyperbeam session
            $ch = curl_init($cancel_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $api_key",
                "Content-Type: application/json"
            ]);
            $cancelResponse = curl_exec($ch);

            // If a cURL error occurs, log it (optional)
            if (curl_errno($ch)) {
                error_log("Error cancelling Hyperbeam session for user $userId: " . curl_error($ch));
            }
            curl_close($ch);

            // Remove the session record from the database using a prepared statement
            $stmtDelete = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
            $stmtDelete->bind_param("i", $userId);
            $stmtDelete->execute();
            $stmtDelete->close();
        } else {
            error_log("No Hyperbeam session_id found for user $userId.");
        }
    }
    $stmt->close();
}

// Remove session cookie, if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear local PHP session variables and destroy the session
session_unset();
session_destroy();

// Redirect the user to the login page
header("Location: login.php", true, 302);
exit;
?>