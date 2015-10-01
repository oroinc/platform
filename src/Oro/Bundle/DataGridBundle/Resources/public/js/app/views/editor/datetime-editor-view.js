define(function(require) {
    'use strict';

    var DatetimeEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var TextEditorView = require('./text-editor-view');
    var DatetimepickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');

    DatetimeEditorView = TextEditorView.extend({
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
            DatetimeEditorView.__super__.render.call(this);
            this.view = new DatetimepickerView(this.getViewOptions());
            // fix enter behaviour
            var events = {};
            events['keydown' + this.eventNamespace()] = _.bind(this.onInternalEnterKeydown, this);
            this.$('.hasDatepicker').on(events);
            // fix esc behaviour
            events = {};
            events['keydown' + this.eventNamespace()] = _.bind(this.onInternalEscapeKeydown, this);
            this.$('.hasDatepicker').on(events);
        },

        onInternalEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE) {
                // there is no other way to get if datepicker is visible
                if ($('#ui-datepicker-div').is(':visible')) {
                    this.$('.hasDatepicker').datepicker('hide');
                } else {
                    DatetimeEditorView.__super__.onInternalEnterKeydown.apply(this, arguments);
                }
            }
        },

        onInternalEscapeKeydown: function(e) {
            if (e.keyCode === this.ESCAPE_KEY_CODE) {
                // there is no other way to get if datepicker is visible
                if ($('#ui-datepicker-div').is(':visible')) {
                    this.$('.hasDatepicker').datepicker('hide');
                } else {
                    DatetimeEditorView.__super__.onInternalEscapeKeydown.apply(this, arguments);
                }
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.hasDatepicker').off(this.eventNamespace());
            this.view.dispose();
            DatetimeEditorView.__super__.dispose.call(this);
        },

        getViewOptions: function() {
            return $.extend(true, {}, this.DEFAULT_OPTIONS,
                _.pick(this.options, ['dateInputAttrs', 'datePickerOptions', 'timeInputAttrs', 'timePickerOptions']), {
                    el: this.$('input[name=value]')
                });
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

    return DatetimeEditorView;
});
