'use strict';
document.addEventListener('DOMContentLoaded', function () {
    const firstInput = document.querySelector('form input:not([type=hidden]):not([type=range])');
    if (firstInput) firstInput.focus();

    const flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity .5s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 500);
        }, 5000);
    }
});
