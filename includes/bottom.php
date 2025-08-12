
   
<script>
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement; // Or document.body

    // Function to set the theme and update UI
    function setTheme(theme) {
        htmlElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme); // Save preference
        updateSwitchUI(theme); // Update the visual state of the switch
    }

    // Function to update the visual state of the theme switch button
    function updateSwitchUI(currentTheme) {
        if (themeToggle) { // Ensure the button exists
            themeToggle.classList.toggle('dark-mode', currentTheme === 'dark');
            // No need to toggle icon opacity here directly via JS, CSS handles it
        }
    }

    // Get preferred theme from local storage or system preference
    const storedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Apply the stored theme, system preference, or default
    if (storedTheme) {
        setTheme(storedTheme);
    } else if (systemPrefersDark) {
        setTheme('dark');
    } else {
        setTheme('light'); // Default to light if no preference is found
    }

    // Event listener for the theme toggle button
    if (themeToggle) { // Ensure the button exists before adding listener
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }

    // Call updateSwitchUI on initial load to ensure the switch reflects the correct theme
    updateSwitchUI(storedTheme || (systemPrefersDark ? 'dark' : 'light'));
});
</script>
    <script src="/public/assets/vendor/libs/popper/popper.js"></script>
    <script src="/public/assets/vendor/js/bootstrap.js"></script>
    <script src="/public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="/public/assets/vendor/js/menu.js"></script>

    <script src="/public/assets/js/main.js"></script>
    <script src="/public/js/loading-states.js"></script>
    <script src="/public/js/chart-debug.js"></script>

    <script async defer src="https://buttons.github.io/buttons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   
</body>

</html>