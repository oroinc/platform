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

        events: {
            'keydown .hasDatepicker': 'onDateEditorKeydown',
            'keydown .timepicker-input': 'onTimeEditorKeydown'
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

        focus: function(atEnd) {
            if (!atEnd) {
                this.$('input.hasDatepicker').setCursorToEnd().focus();
            } else {
                this.$('input.timepicker-input').setCursorToEnd().focus();
            }
        },

        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            var parsed;
            try {
                parsed = datetimeFormatter.getMomentForBackendDateTime(raw);
            } catch (e) {
                return null;
            }
            // ignore seconds to avoid excessive server requests
            parsed.seconds(0);
            return parsed;
        },

        getFormattedValue: function() {
            var value = this.getModelValue();
            if (value === null) {
                return '';
            }
            return value.format(datetimeFormatter.backendFormats.datetime);
        },

        getValue: function() {
            var raw = this.$('input[name=value]').val();
            return !raw ? null : moment.utc(raw, datetimeFormatter.backendFormats.datetime);
        },

        isChanged: function() {
            var value = this.getValue();
            var modelValue = this.getModelValue();
            if (value !== null && modelValue !== null) {
                return value.diff(modelValue);
            }
            return value !== modelValue;
        },

        onDateEditorKeydown: function(e) {
            // stop propagation to prevent default behaviour
            if (!e.shiftKey) {
                e.stopPropagation();
            }
        },

        onTimeEditorKeydown: function(e) {
            // stop propagation to prevent default behaviour
            if (e.shiftKey) {
                e.stopPropagation();
            }
        }
    });

    return DateEditorView;
});
