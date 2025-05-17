<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</title>
   <link rel="stylesheet" href="styles.css">
   <script type="module">
      // ----- Auto-Logout Timer -----
      var sessionTimeoutSeconds = <?php echo (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) ? 3600 : 1800; ?>;
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
            if (e.clientY < 50) { // if mouse is near the top, show the header.
               showHeader();
            }
         });
         header.addEventListener("mouseover", showHeader);
         header.addEventListener("mouseout", function(){
             headerHideTimer = setTimeout(function(){
                 header.classList.add("hidden");
             }, 3000);
         });
      });

      // ----- Hyperbeam VM Initialization -----
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
      window.addEventListener("load", initVirtualComputer);
   </script>
</head>
<body>
   <!-- Auto-Hiding Header -->
   <header id="topHeader">
      <span>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</span>
      <nav>
         <a href="logout.php">Logout</a>
         <?php if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) { ?>
            <a href="admin.php">Admin Panel</a>
         <?php } ?>
      </nav>
   </header>
   <main>
      <div id="hyperbeamContainer">
         <div id="virtualComputerDiv"></div>
      </div>
   </main>
</body>
</html>
