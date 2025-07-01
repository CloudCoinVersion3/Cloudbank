document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu = document.querySelector('.mobile-menu');
    const closeIcon = document.querySelector('.close-icon');
    const navLinks = document.querySelectorAll('.mobile-menu .nav-links li');

    function toggleMobileMenu() {
        hamburger.classList.toggle('active');
        mobileMenu.classList.toggle('active');

        if (mobileMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            navLinks.forEach((link, index) => {
                setTimeout(() => {
                    link.classList.add('show');
                }, 100 * (index + 1));
            });
        } else {
            document.body.style.overflow = '';
            navLinks.forEach(link => {
                link.classList.remove('show');
            });
        }
    }

    hamburger.addEventListener('click', toggleMobileMenu);
    closeIcon.addEventListener('click', toggleMobileMenu);

    // Close mobile menu when a link is clicked
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileMenu.classList.contains('active')) {
                toggleMobileMenu();
            }
        });
    });

     // Ensure main page spinner is hidden
    // const spinner = document.getElementById('spinner');
    // if (spinner) {
    //     spinner.classList.add('hidden');
    //     spinner.style.display = 'none';
    // }
    
    // // Hide the main message div when on phone verification
    // const mainMessageDiv = document.getElementById('message');
    // const phoneForm = document.getElementById('loginForm');
    
    // if (phoneForm && mainMessageDiv) {
    //     // Hide main message div since verification form has its own
    //     mainMessageDiv.style.display = 'none';
    // }


    // Close mobile menu on window resize if it's open
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && mobileMenu.classList.contains('active')) {
            toggleMobileMenu();
        }
    });
});