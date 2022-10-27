require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/mediator', 'oroui/js/messenger',
    'oro/dialog-widget', 'jquery.dialog.extended'],
function($, _, __, mediator, messenger, DialogWidget) {
    'use strict';

    /* ============================================================
     * from user.js
     * ============================================================ */
    $(function() {
        function checkRoleInputs() {
            const inputs = $('#roles-list').find('.controls :checkbox');
            inputs.attr('required', inputs.filter(':checked').length > 0 ? null : 'required');
        }

        $(document).on('click', '#roles-list input', function() {
            checkRoleInputs();
        });

        /**
         * Process role checkboxes after navigation request is completed
         */
        mediator.on('page:afterChange', checkRoleInputs);
    });
});
