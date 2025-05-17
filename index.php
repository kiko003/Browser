<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}
$isAdmin = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"];
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Welcome, <?php echo htmlspecialchars($username); ?>!</title>
   <link rel="stylesheet" href="styles.css?v=2">
   <style>
      #hyperbeamContainer { display: none; }
      .admin-title { color: #8e44ad; }
      .user-title { color: #28a745; }
   </style>
   <script type="module">
      // ----- Auto-Logout Timer -----
      var sessionTimeoutSeconds = <?php echo $isAdmin ? 3600 : 1800; ?>;
      var logoutTimer;
      function resetLogoutTimer() {
         if (logoutTimer) { clearTimeout(logoutTimer); }
         logoutTimer = setTimeout(function(){
            window.location.href = "logout.php";
         }, sessionTimeoutSeconds * 1000);
      }
      document.addEventListener("mousemove", resetLogoutTimer);
      document.addEventListener("keypress", resetLogoutTimer);
      resetLogoutTimer();

      // ----- Auto-Hiding Header Logic -----
      let header;
      let headerHideTimer;
      function showHeader() {
         header.classList.remove("hidden");
         clearTimeout(headerHideTimer);
         headerHideTimer = setTimeout(function(){
            header.classList.add("hidden");
         }, 3000);
      }
      window.addEventListener("load", function(){
         header = document.getElementById("topHeader");
         showHeader();
         document.addEventListener("mousemove", function(e) {
            if (e.clientY < 50) { showHeader(); }
         });
         header.addEventListener("mouseover", showHeader);
         header.addEventListener("mouseout", function(){
            headerHideTimer = setTimeout(function(){
               header.classList.add("hidden");
            }, 3000);
         });
      });

      // ----- Hyperbeam VM Initialization (on demand) -----
      import Hyperbeam from "https://unpkg.com/@hyperbeam/web@latest/dist/index.js";
      let hyperbeamInstance = null;
      async function initVirtualComputer() {
         try {
             const response = await fetch("computer.php");
             const data = await response.json();
             hyperbeamInstance = await Hyperbeam(document.getElementById("virtualComputerDiv"), data.embed_url);
             hyperbeamInstance.onReady(() => {
                console.log("Hyperbeam session is ready!");
             });
         } catch (error) {
             console.error("Error initializing Hyperbeam session:", error);
         }
      }
      window.addEventListener("DOMContentLoaded", function() {
         document.getElementById('showVmBtn').addEventListener('click', function() {
            document.getElementById('welcomeBox').style.display = 'none';
            document.getElementById('hyperbeamContainer').style.display = 'block';
            initVirtualComputer();
         });
      });
   </script>
</head>
<body>
   <header id="topHeader">
      <span>
         <strong><?php echo $isAdmin ? 'Admin' : 'User'; ?> Panel</strong>
         &nbsp;|&nbsp;
         Welcome, <?php echo htmlspecialchars($username); ?>!
      </span>
      <nav>
         <a href="index.php">Main Page</a>
         <?php if($isAdmin) { ?>
            <a href="admin.php">Admin Panel</a>
         <?php } ?>
         <a href="logout.php">Logout</a>
      </nav>
   </header>
   <main>
      <div class="form-box" style="max-width: 600px;" id="welcomeBox">
         <h2 style="margin-bottom:18px;">
            <?php if ($isAdmin): ?>
               <span class="admin-title">HEY ADMIN!</span>
            <?php else: ?>
               <span class="user-title">Welcome</span>
            <?php endif; ?>
         </h2>
         <p style="text-align:center; margin: 0 0 15px 0;">
            Hello, <strong><?php echo htmlspecialchars($username); ?></strong>!
            <?php if ($isAdmin): ?>
               <br>You are logged in as <strong>admin</strong>.<br>
               Use the Admin Panel to manage users and VMs.<br>
            <?php else: ?>
               <br>You are logged in.<br>
               Use the menu above to navigate.<br>
            <?php endif; ?>
         </p>
         <button id="showVmBtn" class="action-btn" style="margin-top:18px;">Show My Virtual Machine</button>
      </div>
        <div id="hyperbeamContainer" style="width:100%;">
            <div id="virtualComputerDiv" style="width:100%; height:600px;"></div>
        </div>
      </div>
   </main>
</body>
</html>