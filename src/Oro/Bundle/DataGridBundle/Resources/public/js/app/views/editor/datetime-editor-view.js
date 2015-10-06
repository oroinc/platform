define(function(require) {
    'use strict';

    var DatetimeEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var DateEditorView = require('./date-editor-view');
    var DatetimepickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');

    DatetimeEditorView = DateEditorView.extend({
        className: 'datetime-editor',
        inputType: 'hidden',
        view: DatetimepickerView,

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

        format: datetimeFormatter.backendFormats.datetime,

        /**
         * @inheritDoc
         */
        render: function() {
            var _this = this;
            DatetimeEditorView.__super__.render.call(this);
            // fix ESCAPE time-picker behaviour
            // must stopPropagation on ESCAPE, if time-picker was visible
            this.$('.timepicker-input').bindFirst('keydown' + this.eventNamespace(), function(e) {
                if (e.keyCode === _this.ESCAPE_KEY_CODE && $('.ui-timepicker-wrapper').css('display') === 'block') {
                    e.stopPropagation();
                }
            });
            return this;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.timepicker-input').off(this.eventNamespace());
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
