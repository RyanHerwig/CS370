var logged_in = sessionStorage.getItem('logged_in') === 'true';

document.addEventListener("DOMContentLoaded", function () {
    const loginButton = document.getElementById("login");
    const navbar = document.getElementById("navbar");
    const navbarLoginLink = document.querySelector('#navbar a[href="login.html"]');

    // Function to update the navbar
    function updateNavbar() {
        if (logged_in) {
            const username = sessionStorage.getItem('username'); // Get username from session storage
            if (navbar) {
                // Hide Login link when logged in
                if (navbarLoginLink) {
                    navbarLoginLink.style.display = 'none';
                }

                // Check if the welcome message already exists
                if (!document.getElementById('welcome-message')) {
                    const welcomeMessage = document.createElement('a');
                    welcomeMessage.id = 'welcome-message';
                    welcomeMessage.href = '#'; // Keep as # for click handling
                    welcomeMessage.style.float = 'right';
                    welcomeMessage.style.marginRight = '10px';
                    welcomeMessage.textContent = `Logout`;
                    welcomeMessage.addEventListener('click', showLogoutPopup); //logout popup
                    navbar.appendChild(welcomeMessage);
                }
                // Check if panel button already exists
                if (!document.getElementById('panel-button')) {
                    const panelButton = document.createElement('a');
                    panelButton.id = 'panel-button';
                    panelButton.href = "php_scripts/admin_panel.php";
                    panelButton.textContent = 'Panel';
                    navbar.appendChild(panelButton);
                }
            }
        } else {
             // Show Login link when logged out 
             if (navbarLoginLink) {
                navbarLoginLink.style.display = '';
            }

            // Remove logout button if logged out
            const welcomeMessage = document.getElementById('welcome-message');
            if (welcomeMessage) {
                welcomeMessage.remove();
            }
            // Remove panel button if logged out
            const panelButton = document.getElementById('panel-button');
            if (panelButton) {
                panelButton.remove();
            }
        }
    }
    // Call to check if already logged in
    updateNavbar();

    function showLogoutPopup() {
        if (confirm('Are you sure you want to logout?')) {
            logout();
        }
    }
        //logout
        function logout() {

            // stupid directory logic
            let logoutPath;
            // Check if the current page's path ends with the admin panel file within php_scripts
            if (window.location.pathname.endsWith('/php_scripts/admin_panel.php')) {
                 // If we are on the admin panel, logout.php is in the same directory
                logoutPath = 'logout.php';
            } else {
                // Otherwise (from root HTML files), logout.php is inside php_scripts/
                logoutPath = 'php_scripts/logout.php';
            }

            fetch(logoutPath)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Logout failed with status: ${response.status}`);
                    }
                     // Do not call response.json() if the response is empty
                        return response.text().then(text => {
                        try {
                            // Try to parse if text exists, otherwise return empty object
                            return text ? JSON.parse(text) : {}
                         } catch (e) {
                           console.warn("Logout response was not valid JSON:", text);
                           return {};
                         }
                    });
                })
                .then(data => {
                    if (data.success && data.redirect) {
                       logged_in = false;
                        sessionStorage.setItem('logged_in', 'false');
                        sessionStorage.removeItem('username');
                        updateNavbar();
                        window.location.href = data.redirect;
                    } else {
                         throw new Error("Logout failed or redirect URL missing from response.");
                    }
                })
                .catch(error => {
                    console.error("Logout error:", error);
                    alert("An error occurred during logout. Please try again."); // User-friendly message
                });
    }

    // Login form submission on login.html
    if (loginButton) {
        loginButton.addEventListener("click", async function () {
            const username = document.getElementById("username").value.trim();
            const password = document.getElementById("password").value.trim();

            if (!username || !password) {
                alert("Please enter both username and password.");
                return;
            }

            try {
                const response = await fetch("php_scripts/login.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                });

                const result = await response.json();
                console.log(result);

                if (result.success) {
                    alert("Login successful!");
                    logged_in = true;
                    sessionStorage.setItem('logged_in', 'true');
                    sessionStorage.setItem('username', username); // Store username in session storage
                    updateNavbar(); // Update navbar AFTER successful login
                    window.location.href = "index.html"; // Correct relative to root HTML
                } else {
                    alert(result.error || "Login failed.");
                }
            } catch (error) {
                alert("An error occurred during login.");
                console.error(error);
            }
        });
    }
});
