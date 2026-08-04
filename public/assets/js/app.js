document.addEventListener('DOMContentLoaded', function () {
    const bulkToggles = document.querySelectorAll('[data-bulk-toggle]');

    bulkToggles.forEach(function (toggle) {
        const groupName = toggle.getAttribute('data-bulk-toggle');

        if (!groupName) {
            return;
        }

        const checkboxes = document.querySelectorAll('[data-bulk-item="' + groupName + '"]');

        toggle.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = toggle.checked;
            });
        });
    });
});