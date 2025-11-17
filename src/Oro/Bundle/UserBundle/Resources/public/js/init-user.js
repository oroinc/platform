import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import 'jquery.dialog.extended';

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
