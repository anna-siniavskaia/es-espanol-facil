document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('#main-nav');

    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', String(isOpen));
        });
    }

    document.querySelectorAll('.choice-option input').forEach((input) => {
        input.addEventListener('change', () => {
            const name = input.getAttribute('name');
            const safeName = name.replace(/"/g, '\\"');
            document.querySelectorAll(`input[name="${safeName}"]`).forEach((peer) => {
                peer.closest('.choice-option')?.classList.toggle('is-selected', peer.checked);
            });
        });

        if (input.checked) {
            input.closest('.choice-option')?.classList.add('is-selected');
        }
    });

    document.querySelectorAll('.panel-form[novalidate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const login = form.querySelector('input[name="login"]');
            const email = form.querySelector('input[name="email"]');

            if (login && login.value.trim().length < 3) {
                event.preventDefault();
                showInlineError(login, 'Минимум 3 символа.');
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                event.preventDefault();
                showInlineError(email, 'Введите корректную почту.');
            }
        });
    });

    document.querySelectorAll('.flash').forEach((flash) => {
        window.setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-6px)';
        }, 4200);
    });
});

function showInlineError(input, message) {
    input.setAttribute('aria-invalid', 'true');
    let error = input.parentElement.querySelector('.inline-error');
    if (!error) {
        error = document.createElement('span');
        error.className = 'inline-error';
        input.parentElement.appendChild(error);
    }
    error.textContent = message;
}
