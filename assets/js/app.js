document.addEventListener('click', function (event) {
    var backButton = event.target.closest('[data-back]');
    if (backButton) {
        event.preventDefault();
        var sameOrigin = document.referrer !== '' && new URL(document.referrer).origin === window.location.origin;
        if (sameOrigin && window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = backButton.getAttribute('data-back') || 'dashboard.php';
        }
        return;
    }

    var clearButton = event.target.closest('[data-clear-form]');
    if (clearButton) {
        event.preventDefault();
        if (!confirm('ต้องการล้างข้อมูลที่กรอกทั้งหมดในฟอร์มนี้หรือไม่?')) {
            return;
        }
        var form = clearButton.closest('form');
        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (field.disabled || field.readOnly) {
                return;
            }
            var type = (field.type || '').toLowerCase();
            if (['hidden', 'submit', 'button', 'reset', 'checkbox', 'radio'].indexOf(type) !== -1) {
                return;
            }
            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
        form.querySelectorAll('.error').forEach(function (error) {
            error.remove();
        });
        var firstField = form.querySelector('input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
        if (firstField) {
            firstField.focus();
        }
    }
});
