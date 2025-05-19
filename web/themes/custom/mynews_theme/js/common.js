document.addEventListener('DOMContentLoaded', function () {
    const infoIcons = document.querySelectorAll('.info-icon');
  
    infoIcons.forEach(function (icon) {
      let timeout;
  
      icon.addEventListener('mouseenter', function () {
        clearTimeout(timeout); // Clear if quickly re-enter
        const tooltip = icon.querySelector('.hover-body-text');
        tooltip.style.display = 'block';
      });
  
      icon.addEventListener('mouseleave', function () {
        const tooltip = icon.querySelector('.hover-body-text');
        timeout = setTimeout(function () {
          tooltip.style.display = 'none';
        }, 300); // 300ms delay before hiding
      });
    });

    //dark mode js
    const toggleButton = document.getElementById('theme-toggle');
    const body = document.body;

    // Function to update button text
    function updateButtonText() {
        if (body.classList.contains('dark-mode')) {
            toggleButton.textContent = '☀️';
        } else {
            toggleButton.textContent = '🌙';
        }
    }

    // Check if user already selected a theme
    if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
    }

    // Update button text on page load
    updateButtonText();

    toggleButton.addEventListener('click', function () {
        body.classList.toggle('dark-mode');

        // Save user preference
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }

        // Update text after toggling
        updateButtonText();
    });
});
  