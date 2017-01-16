require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/mediator', 'oroui/js/messenger',
    'oro/dialog-widget', 'jquery.dialog.extended'],
function($, _, __, mediator, messenger, DialogWidget) {
    'use strict';

    /* ============================================================
     * from user.js
     * ============================================================ */
    $(function() {
        function checkRoleInputs() {
            var inputs = $('#roles-list').find('.controls :checkbox');
            inputs.attr('required', inputs.filter(':checked').length > 0 ? null : 'required');
        }

        function initFlashMessages() {
            messenger.setup();
        }

        $(document).on('click', '#roles-list input', function() {
            checkRoleInputs();
        });

        /**
         * Process role checkboxes after navigation request is completed
         */
        mediator.on('page:afterChange', checkRoleInputs);

        /**
         * Process flash messages stored in queue or storage
         */
        mediator.on('page:afterChange', initFlashMessages);

        $(document).on('change', '#btn-enable input', function() {
            $('.status-enabled').toggleClass('hide');
            $('.status-disabled').toggleClass('hide');
        });
    });

    /* ============================================================
     * from status.js
     * ============================================================ */
    $(function() {
        var dialogBlock;

        $('.update-status a').click(function() {
            $.get($(this).attr('href'), function(data) {
                dialogBlock = $(data).dialog({
                    title: __('oro.user.update_status.label'),
                    width: 300,
                    height: 180,
                    modal: false,
                    resizable: false
                });
            });

            return false;
        });

        $(document).on('submit', '#create-status-form', function() {
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                success: function() {
                    dialogBlock.dialog('destroy');
                }
            });

            return false;
        });
    });
});
