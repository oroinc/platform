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

        $(document).on('click', '#view-activity-btn', function(e) {
            e.stopImmediatePropagation();
            var $el = $(this);
            var dialog = /** @var oro.DialogWidget */ $el.data('dialog');
            if (dialog) {
                // dialog already is opened
                return false;
            }

            $el.data('dialog', dialog = new DialogWidget({
                url: $el.attr('href'),
                dialogOptions: {
                    allowMaximize: true,
                    allowMinimize: true,
                    dblclick: 'maximize',
                    maximizedHeightDecreaseBy: 'minimize-bar',
                    width : 1000,
                    title: $el.attr('title')
                }
            }));
            dialog.once('widgetRemove', _.bind($el.removeData, $el, 'dialog'));
            dialog.render();

            return false;
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
                type:'POST',
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
