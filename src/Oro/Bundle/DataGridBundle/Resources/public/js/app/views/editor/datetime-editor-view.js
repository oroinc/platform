define(function(require) {
    'use strict';

    var DateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var TextEditorView = require('./text-editor-view');
    var DatetimepickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');

    DateEditorView = TextEditorView.extend({
        className: 'datetime-editor',
        inputType: 'hidden',

        DEFAULT_OPTIONS: {
            dateInputAttrs: {
                placeholder: __('oro.form.choose_date')
            },
            datePickerOptions: {
                altFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '-80:+1',
                showButtonPanel: true
            },
            timeInputAttrs: {
                placeholder: __('oro.form.choose_time'),
                'class': 'input-small timepicker-input'
            },
            timePickerOptions: {
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            DateEditorView.__super__.render.call(this);
            this.view = new DatetimepickerView($.extend(true, {}, this.DEFAULT_OPTIONS,
                _.pick(this.options, ['dateInputAttrs', 'datePickerOptions', 'timeInputAttrs', 'timePickerOptions']), {
                    el: this.$('input[name=value]')
                }));
        },

        focus: function() {
            this.$('input.hasDatepicker').focus();
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            return datetimeFormatter.getMomentForBackendDateTime(raw);
        },

        getFormattedValue: function() {
            return this.getModelValue().format(datetimeFormatter.backendFormats.datetime);
        },

        getValue: function() {
            var raw = this.$('input[name=value]').val();
            return moment.utc(raw, datetimeFormatter.backendFormats.datetime);
        },

        isChanged: function() {
            return this.getValue().diff(this.getModelValue());
        }
    });

    return DateEditorView;
});
