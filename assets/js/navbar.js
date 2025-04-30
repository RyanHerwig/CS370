const menuButton = document.getElementById("hamburger-menu");
const navbar = document.getElementById("navbar");

let navbarVisible = true;

function displayNavbar(){
    if(navbarVisible){
        navbar.style.height = "0px";
        navbar.style.padding = "0px";
        navbarVisible = false;
    }else{
        navbar.style = "padding: 10px, 0px";
        navbar.style.position = "sticky";
        navbar.style.top = 0;
        navbarVisible = true;
    }
}