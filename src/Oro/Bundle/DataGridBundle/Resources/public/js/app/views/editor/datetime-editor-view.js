/** @lends DatetimeEditorView */
define(function(require) {
    'use strict';

    /**
     * Datetime cell content editor
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Mapped by frontend type
     *       {column-name-1}:
     *         frontend_type: datetime
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         inline_editing:
     *           editor:
     *             view: orodatagrid/js/app/views/editor/date-editor-view
     *             view_options:
     *               css_class_name: '<class-name>'
     *               datePickerOptions:
     *                 # See http://goo.gl/pddxZU
     *                 altFormat: 'yy-mm-dd'
     *                 changeMonth: true
     *                 changeYear: true
     *                 yearRange: '-80:+1'
     *                 showButtonPanel: true
     *               timePickerOptions:
     *                 # See https://github.com/jonthornton/jquery-timepicker#options
     *           validation_rules:
     *             NotBlank: ~
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for the date HTML input element
     * inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://goo.gl/pddxZU)
     * inline_editing.editor.view_options.timeInputAttrs   | Optional. Attributes for the time HTML input element
     * inline_editing.editor.view_options.timePickerOptions| Optional. See [documentation here](https://goo.gl/MP6Unb)
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     * @param {Object} options.dateInputAttrs - Attributes for date HTML input element
     * @param {Object} options.datePickerOptions - See [documentation here](http://goo.gl/pddxZU)
     * @param {Object} options.timeInputAttrs - Attributes for time HTML input element
     * @param {Object} options.timePickerOptions - See [documentation here](https://goo.gl/MP6Unb)
     *
     * @augments [DateEditorView](./date-editor-view.md)
     * @exports DatetimeEditorView
     */
    var DatetimeEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var DateEditorView = require('./date-editor-view');
    var DatetimepickerView = require('oroui/js/app/views/datepicker/datetimepicker-view');

    DatetimeEditorView = DateEditorView.extend(/** @exports DatetimeEditorView.prototype */{
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

        format: datetimeFormatter.getBackendDateTimeFormat(),

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
            // fix arrows behaviour
            this.$('.timepicker-input').on('keydown' + this.eventNamespace(), _.bind(this.onGenericArrowKeydown, this));
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
