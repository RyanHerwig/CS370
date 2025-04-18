function copyText() {
    // Copy text
    navigator.clipboard.writeText("ryanzherwig@gmail.com");

    popUp();
}

//Helper Variable
var timeout;
function popUp() {
    //Gets pop up
    var popUp = document.getElementById("Pop Up");
    popUp.classList.toggle("show"); //Toggles showing the pop up

    //Erases the current timeout, if any.
    //Prevents glitches if user spams button
    window.clearTimeout(timeout);

    //Starts a timeout
    timeout = setTimeout(function()
    {
        //If the pop up is visible when timeout runs out, hide it
        //Prevents glitches if user spams buttons
        if (popUp.classList.value === "popuptext show")
            popUp.classList.toggle("show");
    }, 2000); //2 seconds before timeout runs out
  }