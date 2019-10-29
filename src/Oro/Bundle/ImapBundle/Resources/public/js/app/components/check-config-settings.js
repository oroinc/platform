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
            const isEnabled = $(controlCheckbox).is(':checked');
            configValues.each(function() {
                $(this).closest('.control-group').toggle(isEnabled);
                $(this).enable(isEnabled);
            });
        }

        const $el = $(options._sourceElement);
        const $parentContainer = $el.parent().parent();
        const useImap = $parentContainer.find('.imap-config:checkbox');
        const useSmtp = $parentContainer.find('.smtp-config:checkbox');
        const imapFields = $parentContainer.find('input.imap-config,select.imap-config').not(':checkbox');
        const smtpFields = $parentContainer.find('input.smtp-config,select.smtp-config').not(':checkbox');

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
