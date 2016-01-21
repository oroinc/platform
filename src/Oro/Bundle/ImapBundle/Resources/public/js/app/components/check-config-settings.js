define(['jquery', 'underscore'], function($, _) {
    'use strict';

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function(options) {
        var $el = $(options._sourceElement);
        var $parentContainer = $el.parent().parent();
        var useImap = $parentContainer.find('.imap-config:checkbox');
        var useSmtp = $parentContainer.find('.smtp-config:checkbox');
        var imapFields = $parentContainer.find('input.imap-config,select.imap-config').not(':checkbox');
        var smtpFields = $parentContainer.find('input.smtp-config,select.smtp-config').not(':checkbox');

        if (useImap.prop('checked') === false) {
            imapFields.each(function() {
                $(this).parents('.control-group').hide();
                $(this).enable(false);
            });
        }
        if (useSmtp.prop('checked') === false) {
            smtpFields.each(function() {
                $(this).parents('.control-group').hide();
                $(this).enable(false);
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
                    $(this).enable();
                });
            } else {
                configValues.each(function() {
                    $(this).parents('.control-group').hide();
                    $(this).enable(false);
                });
            }
        };
    };
});
