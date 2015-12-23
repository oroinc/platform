define(['jquery', 'underscore', 'oroui/js/mediator'], function($, _, mediator) {
    'use strict';

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    return function(options) {
        debugger;
        var self = this;

        this.options = options;

        var processChange = function () {
            //this.memoizeValue($el);
debugger;
            var $form = $(this.options.formSelector);
            var data = $form.serializeArray();
            var url = $form.attr('action');
            //var fieldsSet = $el.is(this.options.typeSelector) ? this.fieldsSets.type : this.fieldsSets.transportType;
            //
            //data = _.filter(data, function(field) {
            //    return _.indexOf(fieldsSet, field.name) !== -1;
            //});
            //data.push({name: this.UPDATE_MARKER, value: 1});

            //var data = [];

            var event = {formEl: $form, data: data, reloadManually: true};
            //mediator.trigger('integrationFormReload:before', event);

            if (event.reloadManually) {
                mediator.execute('submitPage', {url: url, type: $form.attr('method'), data: $.param(data)});
            }
        };


        $('select[name="oro_user_user_form[accountType]"]').change(_.bind(processChange, self));

        //var $el = $(options._sourceElement);
        //var $parentContainer = $el.parent();
        //var useImap = $parentContainer.find('.imap-config:checkbox');
        //var useSmtp = $parentContainer.find('.smtp-config:checkbox');
        //var imapFields = $parentContainer.find('input.imap-config,select.imap-config').not(':checkbox');
        //var smtpFields = $parentContainer.find('input.smtp-config,select.smtp-config').not(':checkbox');
        //
        //if (useImap.prop('checked') === false) {
        //    imapFields.each(function() {
        //        $(this).parents('.control-group').hide();
        //        $(this).enable(false);
        //    });
        //}
        //if (useSmtp.prop('checked') === false) {
        //    smtpFields.each(function() {
        //        $(this).parents('.control-group').hide();
        //        $(this).enable(false);
        //    });
        //}
        //
        //$(useImap).on('change', function() {
        //    configShowHide(useImap, imapFields);
        //});
        //$(useSmtp).on('change', function() {
        //    configShowHide(useSmtp, smtpFields);
        //});
        //
        //var configShowHide = function(controlCheckbox, configValues) {
        //    if (controlCheckbox.is(':checked')) {
        //        configValues.each(function() {
        //            $(this).parents('.control-group').show();
        //            $(this).enable();
        //        });
        //    } else {
        //        configValues.each(function() {
        //            $(this).parents('.control-group').hide();
        //            $(this).enable(false);
        //        });
        //    }
        //};
    };
});
