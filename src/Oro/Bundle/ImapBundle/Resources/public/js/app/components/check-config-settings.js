define(['jquery', 'underscore'], function($, _) {
    'use strict';

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function(options) {
        function setVisibility(controlCheckbox, configValues) {
            var isEnabled = $(controlCheckbox).is(':checked');
            configValues.each(function() {
                $(this).closest('.control-group').toggle(isEnabled);
                $(this).enable(isEnabled);
            });
        }

        var $el = $(options._sourceElement);
        var $parentContainer = $el.parent().parent();
        var useImap = $parentContainer.find('.imap-config:checkbox');
        var useSmtp = $parentContainer.find('.smtp-config:checkbox');
        var imapFields = $parentContainer.find('input.imap-config,select.imap-config').not(':checkbox');
        var smtpFields = $parentContainer.find('input.smtp-config,select.smtp-config').not(':checkbox');

        setVisibility(useImap, imapFields);
        setVisibility(useSmtp, smtpFields);

        $(useImap).on('change', function() {
            setVisibility(this, imapFields);
        });
        $(useSmtp).on('change', function() {
            setVisibility(this, smtpFields);
        });
    };
});
