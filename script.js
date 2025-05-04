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
                logoutLink.style.marginRight = '60px';
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

    // hide/display navbar functionality
    const body = document.body;
    const styleTag = document.getElementsByTagName("style")[0];

    const menuButton = document.createElement("div");
    menuButton.id = "hamburger-menu";
    menuButton.onclick = displayNavbar;
    menuButton.innerHTML = "☰";

    styleTag.innerHTML += "\n#hamburger-menu{"+
                                "top: 10px;"+
                                "right: 0px;"+
                                "position: fixed;"+
                                "overflow: hidden;"+
                                "z-index: 101;"+
                                "cursor: pointer;"+
                                "background-color: #333;"+
                                "display: inline-block;"+
                                "color: #f2f2f2;"+
                                "text-align: center;"+
                                "padding: 14px 16px;"+
                                "text-decoration: none;"+
                                "font-size: 17px;"+
                                "margin: 0 5px;"+
                                "border-radius: 5px;"+
                            "}\n"+
                            "#hamburger-menu:hover {"+
                                "background-color: #ddd;"+
                                "color: black;"+
                                "cursor: pointer;"+
                            "}\n";

    styleTag.innerHTML += "#navbar{"+
                                "z-index: 100;"+
                                "transition: 1ms;"+
                                "position: sticky;"+
                                "top: 0;"+
                            "}\n";

    menuButton.style = "height: auto;";

    body.insertBefore(menuButton, body.firstChild);

    let navbarVisible = true;

    function displayNavbar(){
        if(navbarVisible){
            navbar.style.height = "0px";
            navbar.style.padding = "0px";
            navbarVisible = false;
        }else{
            navbar.style = "padding: 10px, 0px";
            navbarVisible = true;
        }
    }

});