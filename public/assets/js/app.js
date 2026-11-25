'use strict';

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const inputId = button.getAttribute('aria-controls');
    const input = inputId ? document.getElementById(inputId) : null;
    const icon = button.querySelector('i');

    if (!input || !icon) {
        return;
    }

    button.addEventListener('click', () => {
        const isVisible = input.type === 'password';
        input.type = isVisible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(isVisible));
        button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
        icon.classList.toggle('bi-eye', !isVisible);
        icon.classList.toggle('bi-eye-slash', isVisible);
    });
});

document.querySelectorAll('form').forEach((form) => {
    const password = form.querySelector('input[name="password"]');
    const confirmation = form.querySelector('input[name="password_confirmation"]');

    if (password && confirmation) {
        const feedback = form.querySelector('.form-feedback');
        const validateConfirmation = () => {
            const matches = password.value === confirmation.value;
            confirmation.setCustomValidity(matches ? '' : 'Passwords do not match.');

            if (feedback) {
                feedback.textContent = matches ? '' : 'Passwords do not match.';
            }
        };

        password.addEventListener('input', validateConfirmation);
        confirmation.addEventListener('input', validateConfirmation);
        form.addEventListener('submit', (event) => {
            validateConfirmation();
            if (password.value !== confirmation.value) {
                event.preventDefault();
                confirmation.reportValidity();
            }
        });
    }

    const confirmationMessage = form.getAttribute('data-confirm');
    if (confirmationMessage) {
        form.addEventListener('submit', (event) => {
            if (password && confirmation && password.value !== confirmation.value) {
                return;
            }
            if (!window.confirm(confirmationMessage)) {
                event.preventDefault();
            }
        });
    }
});

const registration = document.getElementById('registration-form');
if (registration) {
    const steps = Array.from(registration.querySelectorAll('[data-registration-step]'));
    const progress = document.querySelectorAll('[data-registration-progress]');
    const back = registration.querySelector('[data-registration-back]');
    const next = registration.querySelector('[data-registration-next]');
    const submit = registration.querySelector('button[type="submit"]');
    let current = 0;

    const showStep = (index, moveFocus = false) => {
        current = index;
        steps.forEach((step, position) => {
            step.hidden = position !== index;
            if (position === index) {
                progress[position]?.setAttribute('aria-current', 'step');
            } else {
                progress[position]?.removeAttribute('aria-current');
            }
        });
        back.hidden = index === 0;
        next.hidden = index === steps.length - 1;
        submit.hidden = !next.hidden;
        if (moveFocus) {
            steps[index].querySelector('input, select')?.focus();
        }
    };

    const advance = () => {
        const invalid = steps[current].querySelector(':invalid');
        if (invalid) {
            invalid.reportValidity();
            return;
        }
        showStep(current + 1, true);
    };

    next.addEventListener('click', advance);
    back.addEventListener('click', () => showStep(current - 1, true));

    registration.addEventListener('invalid', (event) => {
        const step = steps.indexOf(event.target.closest('[data-registration-step]'));
        if (step !== -1 && steps[step].hidden) {
            showStep(step);
        }
    }, true);

    registration.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.isComposing && event.target.matches('input') && !next.hidden) {
            event.preventDefault();
            advance();
        }
    });

    showStep(0);
}
