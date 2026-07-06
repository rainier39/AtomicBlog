// Copy some text to the keyboard.
function copy(text) {
    navigator.clipboard.writeText(text);
    // Clean out old instances of this toast.
    document.getElementById("toasts").innerHTML = document.getElementById("toasts").innerHTML.replace('<div class="success toast">Successfully copied attachment link!</div>', "");
    // Add a toast to notify the user.
    document.getElementById("toasts").innerHTML += "<div class='success toast'>Successfully copied attachment link!</div>";
}
