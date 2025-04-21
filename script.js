var logged_in = sessionStorage.getItem('logged_in') === 'true';

document.addEventListener("DOMContentLoaded", function () {
    const loginButton = document.getElementById("login");
    const navbar = document.getElementById("navbar");
    const navbarLoginLink = document.querySelector('#navbar a[href="login.html"], #navbar a[href="../login.html"]');

    function updateNavbar() {
        const username = sessionStorage.getItem('username');

        if (!navbar) {
            console.error("Navbar element not found!");
            return;
        }

        if (logged_in && username) {

            if (navbarLoginLink) {
                navbarLoginLink.style.display = 'none';
            }

            let logoutLink = document.getElementById('navbar-logout-link');
            if (!logoutLink) {
                logoutLink = document.createElement('a');
                logoutLink.id = 'navbar-logout-link';
                logoutLink.href = '#';
                logoutLink.style.float = 'right';
                logoutLink.style.marginRight = '10px';
                logoutLink.addEventListener('click', showLogoutPopup);
                navbar.appendChild(logoutLink);
            }
            logoutLink.textContent = `Logout`;

            let panelLink = document.getElementById('navbar-panel-link');
            if (username === 'admin') {
                if (!panelLink) {
                    panelLink = document.createElement('a');
                    panelLink.id = 'navbar-panel-link';
                    panelLink.textContent = 'Admin Panel';
                    panelLink.href = '/cs370/php_scripts/admin_panel.php';
                    panelLink.style.float = 'left';
                    panelLink.style.marginLeft = '10px';
                    const aboutLink = Array.from(navbar.getElementsByTagName('a')).find(a => a.textContent === 'About');
                    if (aboutLink && aboutLink.nextSibling) {
                        navbar.insertBefore(panelLink, aboutLink.nextSibling);
                    } else {
                        navbar.appendChild(panelLink);
                    }
                }
            } else {
                if (panelLink) {
                    panelLink.remove();
                }
            }

        } else {
            if (navbarLoginLink) {
                navbarLoginLink.style.display = '';
            }
            const logoutLink = document.getElementById('navbar-logout-link');
            if (logoutLink) {
                logoutLink.remove();
            }
            const panelLink = document.getElementById('navbar-panel-link');
            if (panelLink) {
                panelLink.remove();
            }
        }
    }

    function showLogoutPopup(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            logout();
        }
    }

    function logout() {
        const logoutPath = '/cs370/php_scripts/logout.php';
        console.log("Using absolute logout path:", logoutPath);

        fetch(logoutPath)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Logout failed for URL ${response.url} with status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return text ? JSON.parse(text) : {};
                    } catch (e) {
                        console.warn("Logout response was not valid JSON:", text);
                        return { success: true, redirect: null };
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    logged_in = false;
                    sessionStorage.setItem('logged_in', 'false');
                    sessionStorage.removeItem('username');
                    updateNavbar();
                    let redirectTarget = data.redirect;
                    if (!redirectTarget) {
                        console.warn("Redirect URL missing from logout response, defaulting to index.html");
                        redirectTarget = '/cs370/index.html';
                    }
                    window.location.href = redirectTarget;
                } else {
                    // This case might occur if JSON parsing failed but didn't return success:true
                    throw new Error("Logout response did not indicate success.");
                }
            })
            .catch(error => {
                console.error("Logout error:", error);
                alert("An error occurred during logout. Please try again.");
            });
    }

    if (loginButton) {
        loginButton.addEventListener("click", async function () {
            const usernameInput = document.getElementById("username");
            const passwordInput = document.getElementById("password");
            const username = usernameInput ? usernameInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value.trim() : '';

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

                if (!response.ok) {
                    throw new Error(`Login request failed with status: ${response.status}`);
                }

                const result = await response.json();
                console.log("Login response:", result);

                if (result.success) {
                    logged_in = true;
                    sessionStorage.setItem('logged_in', 'true');
                    sessionStorage.setItem('username', username);
                    window.location.href = "index.html";
                } else {
                    alert(result.error || "Login failed. Please check your username and password.");
                }
            } catch (error) {
                alert("An error occurred during login. Please check the console for details.");
                console.error("Login fetch/processing error:", error);
            }
        });
    }
    updateNavbar();

});