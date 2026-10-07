// Select the navbar
const navbar = document.querySelector('.navbar');

// Disable the navbar
navbar.style.pointerEvents = 'none';
navbar.style.opacity = '0.5';

// Optional: Add a 'not-allowed' cursor for better user feedback
navbar.querySelectorAll('a, button').forEach(el => {
    el.style.cursor = 'not-allowed';
});