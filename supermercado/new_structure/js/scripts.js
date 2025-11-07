document.addEventListener("DOMContentLoaded", () => {
    // Initial fade-in animation
    document.body.style.opacity = "0";
    requestAnimationFrame(() => {
        document.body.style.transition = "opacity 0.5s ease";
        document.body.style.opacity = "1";
    });

    // Form submission animation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            if (button) {
                button.style.transform = 'scale(0.95)';
                button.style.opacity = '0.9';
                setTimeout(() => {
                    button.style.transform = '';
                    button.style.opacity = '';
                }, 150);
            }
        });
    });

    // Input focus effects
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });

        input.addEventListener('blur', function() {
            this.parentElement.style.transform = '';
        });
    });

    // Error message fade out
    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.opacity = '0';
            errorMessage.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                errorMessage.remove();
            }, 300);
        }, 5000);
    }
});