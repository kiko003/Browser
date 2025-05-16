<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Hyperbeam Virtual Computer</title>
</head>
<body>
    <div class="container">
        <!-- Login and Register Forms -->
        <div id="authSection">
            <h2>Welcome to Hyperbeam Virtual Computer</h2>
            <div id="loginForm">
                <h3>Login</h3>
                <form action="login.php" method="post">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Login</button>
                </form>
            </div>

            <div id="registerForm">
                <h3>Register</h3>
                <form action="register.php" method="post">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Register</button>
                </form>
            </div>
        </div>

        <!-- Virtual Computer Section -->
        <div id="computerSection" style="display: none;">
            <button onclick="logout()">Logout</button>
            <button id="gotoYouTubeBtn">Open Youtube.com</button>
            <p>Current website: <span id="currentSite"></span></p>
            <div id="status">Loading Virtual Computer...</div>
            <div id="virtualComputerDiv"></div>
        </div>
    </div>

<script type="module">
    import Hyperbeam from "https://unpkg.com/@hyperbeam/web@latest/dist/index.js";

    async function checkLoginStatus() {
        const response = await fetch("session-check.php");
        const data = await response.json();
        if (data.loggedin) {
            document.getElementById("authSection").style.display = "none";
            document.getElementById("computerSection").style.display = "block";
            loadComputer();
        }
    }

    async function loadComputer() {
        const resp = await fetch("computer.php");
        const data = await resp.json();

        if (data.error) {
            document.body.innerHTML = "<h2>" + data.error + "</h2>";
            return;
        }

        const hb = await Hyperbeam(document.getElementById("virtualComputerDiv"), data.embed_url);
        hb.onReady(() => resizeHyperbeam(hb));
        window.addEventListener("resize", () => resizeHyperbeam(hb));

        document.getElementById("gotoYouTubeBtn").addEventListener("click", () => {
            hb.tabs.update({ url: "https://youtube.com" });
        });

        hb.tabs.onUpdated.addListener((tabId, changeInfo) => {
            if (changeInfo.title) document.getElementById("currentSite").innerText = changeInfo.title;
        });
    }

    function resizeHyperbeam(hb) {
        const maxPixels = hb.maxArea;
        const desiredWidth = window.innerWidth * 0.8;
        const desiredHeight = window.innerHeight * 0.6;
        const aspectRatio = desiredWidth / desiredHeight;

        const height = Math.floor(Math.sqrt(maxPixels / aspectRatio));
        const width = Math.floor(height * aspectRatio);

        hb.resize(width, height);
    }

    function logout() {
        window.location.href = "logout.php";
    }

    // Expose the logout function to the global scope, so the HTML onclick attribute can access it.
    window.logout = logout;

    window.onload = checkLoginStatus;
</script>
</body>
</html>

