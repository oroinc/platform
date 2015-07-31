define(['jquery', 'underscore'], function($, _) {
    'use strict';

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function(options) {
        var useImap = $('.imap-config:checkbox');
        var useSmtp = $('.smtp-config:checkbox');

        var imapFields = $('input.imap-config,select.imap-config').not(':checkbox');
        var smtpFields = $('input.smtp-config,select.smtp-config').not(':checkbox');

        if (useImap.prop('checked') === false) {
            imapFields.each(function() {
                $(this).parents('.control-group').hide();
            });
        }
        if (useSmtp.prop('checked') === false) {
            smtpFields.each(function() {
                $(this).parents('.control-group').hide();
            });
        }

        $(useImap).on('change', function() {
            configShowHide(useImap, imapFields);
        });
        $(useSmtp).on('change', function() {
            configShowHide(useSmtp, smtpFields);
        });

        var configShowHide = function(controlCheckbox, configValues) {
            if (controlCheckbox.is(':checked')) {
                configValues.each(function() {
                    $(this).parents('.control-group').show();
                });
            } else {
                configValues.each(function() {
                    $(this).parents('.control-group').hide();
                });
            }
        };
    };
});
