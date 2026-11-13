/**
 * CourtIQ: A Web-Based Hostel Management System
 * Login Page Interactive Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    const loginSubmitBtn = document.getElementById('loginSubmitBtn');
    const loginSpinner = document.getElementById('loginSpinner');
    const clientErrorBox = document.getElementById('clientErrorBox');
    const clientErrorMessage = document.getElementById('clientErrorMessage');
    const demoButtons = document.querySelectorAll('.btn-demo-fill');

    // 1. Password Visibility Toggle
    if (passwordToggle && passwordInput) {
        const eyeShow = passwordToggle.querySelector('.eye-show');
        const eyeHide = passwordToggle.querySelector('.eye-hide');

        passwordToggle.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            if (isPassword) {
                eyeShow.classList.add('is-hidden');
                eyeHide.classList.remove('is-hidden');
                passwordToggle.setAttribute('aria-label', 'Hide password');
            } else {
                eyeShow.classList.remove('is-hidden');
                eyeHide.classList.add('is-hidden');
                passwordToggle.setAttribute('aria-label', 'Show password');
            }
        });
    }

    // Helper: Show Client Error
    function showClientError(message) {
        if (clientErrorBox && clientErrorMessage) {
            clientErrorMessage.textContent = message;
            clientErrorBox.classList.remove('is-hidden');
        }
    }

    function clearClientError() {
        if (clientErrorBox && clientErrorMessage) {
            clientErrorMessage.textContent = '';
            clientErrorBox.classList.add('is-hidden');
        }
    }

    // 2. Client-side Form Validation & Loading State
    if (loginForm && usernameInput && passwordInput && loginSubmitBtn) {
        loginForm.addEventListener('submit', (e) => {
            clearClientError();

            const usernameVal = usernameInput.value.trim();
            const passwordVal = passwordInput.value;

            if (!usernameVal || !passwordVal) {
                e.preventDefault();
                showClientError('Please enter both your email address/username and password.');
                if (!usernameVal) {
                    usernameInput.focus();
                } else {
                    passwordInput.focus();
                }
                return;
            }

            // Valid submission: show loading feedback
            loginSubmitBtn.disabled = true;
            if (loginSpinner) {
                loginSpinner.classList.remove('is-hidden');
            }
            const btnText = loginSubmitBtn.querySelector('.btn-text');
            if (btnText) {
                btnText.textContent = 'Verifying Credentials...';
            }
        });

        // Clear error on input
        usernameInput.addEventListener('input', clearClientError);
        passwordInput.addEventListener('input', clearClientError);
    }

    // 3. Quick Demo Credentials Auto-Fill
    demoButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const user = btn.getAttribute('data-user');
            const pass = btn.getAttribute('data-pass');

            if (usernameInput && passwordInput) {
                clearClientError();
                usernameInput.value = user;
                passwordInput.value = pass;

                // Visual focus feedback
                passwordInput.focus();
            }
        });
    });
});
