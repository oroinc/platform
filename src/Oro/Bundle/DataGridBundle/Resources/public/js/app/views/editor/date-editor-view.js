define(function(require) {
    'use strict';

    var DateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var TextEditorView = require('./text-editor-view');
    var DatepickerView = require('oroui/js/app/views/datepicker/datepicker-view');

    DateEditorView = TextEditorView.extend({
        className: 'date-editor',
        inputType: 'date',

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
            }
        },

        /**
         * @inheritDoc
         */
        render: function() {
            DateEditorView.__super__.render.call(this);
            this.view = new DatepickerView(this.getDatepickerViewOptions());
        },

        getDatepickerViewOptions: function() {
            return $.extend(true, {}, this.DEFAULT_OPTIONS,
                _.pick(this.options, ['dateInputAttrs', 'datePickerOptions']), {
                    el: this.$('input[name=value]')
                });
        },

        focus: function() {
            this.$('input.hasDatepicker').setCursorToEnd().focus();
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            try {
                return datetimeFormatter.getMomentForBackendDate(raw);
            } catch (e) {
                try {
                    return datetimeFormatter.getMomentForBackendDateTime(raw);
                } catch (e2) {
                    return null;
                }
            }
        },

        getFormattedValue: function() {
            var value = this.getModelValue();
            if (value === null) {
                return '';
            }
            return value.format(datetimeFormatter.backendFormats.date);
        },

        getValue: function() {
            var raw = this.$('input[name=value]').val();
            return !raw ? null : moment.utc(raw, datetimeFormatter.backendFormats.date);
        },

        isChanged: function() {
            var value = this.getValue();
            var modelValue = this.getModelValue();
            if (value !== null && modelValue !== null) {
                return value.diff(modelValue);
            }
            return value !== modelValue;
        }
    });

    return DateEditorView;
});
