// Example: error-handler.js
document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get("error");

    if (error === "id_exists") {
        alert("ID No already exists!");
        // Optional: Clear the error from the URL after showing the alert
        history.replaceState(null, "", window.location.pathname);
    }
});
