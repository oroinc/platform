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
     *             view: oroform/js/app/views/editor/date-editor-view
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
     * @param {string} options.fieldName - Field name to edit in model
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
                placeholder: __('oro.form.choose_date'),
                name: 'date',
                autocomplete: 'off',
                'data-validation': JSON.stringify({Date: {}})
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
                name: 'time',
                autocomplete: 'off',
                'class': 'input-small timepicker-input',
                'data-validation': JSON.stringify({Time: {}})
            },
            timePickerOptions: {
            }
        },

        events: {
            'keydown .hasDatepicker': 'onDateEditorKeydown',
            'keydown .timepicker-input': 'onTimeEditorKeydown',
            'change .hasDatepicker': 'onDateEditorKeydown',
            'change .timepicker-input': 'onTimeEditorKeydown',
            'showTimepicker .ui-timepicker-input': 'onTimepickerShow',
            'hideTimepicker .ui-timepicker-input': 'onTimepickerHide'
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

        onFocusout: function(e) {
            // if blur event was as sequence of time selection in dropdown, returns focus back
            if (this._isTimeSelection) {
                delete this._isTimeSelection;
                this.focus(1);
            } else {
                DatetimeEditorView.__super__.onFocusout.call(this, e);
            }
        },

        parseRawValue: function(value) {
            var parsed;
            try {
                parsed = datetimeFormatter.getMomentForBackendDateTime(value);
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
                this.onGenericTabKeydown(e);
            }
        },

        onTimeEditorKeydown: function(e) {
            // stop propagation to prevent default behaviour
            if (e.shiftKey) {
                e.stopPropagation();
                this.onGenericTabKeydown(e);
            }
        },

        onTimepickerHide: function() {
            this.toggleDropdownBelowClass(false);
        },

        onTimepickerShow: function(e) {
            var $list = this.view.getTimePickerWidget();
            var isBelow = !$list.hasClass('ui-timepicker-positioned-top');
            this.toggleDropdownBelowClass(isBelow);
            $list.off(this.eventNamespace())
                .on('mousedown' + this.eventNamespace(), _.bind(function(e) {
                    // adds flag that blur event was as sequence of time selection in dropdown
                    this._isTimeSelection = true;
                }, this));
        },

        onGenericTabKeydown: function(e) {
            if (e.keyCode === this.TAB_KEY_CODE) {
                if (this.$('input.hasDatepicker').is(e.currentTarget) && !e.shiftKey) {
                    e.preventDefault();
                    this._isTimeSelection = true;
                    this.focus(1);
                } else if (this.$('input.timepicker-input').is(e.currentTarget) && e.shiftKey) {
                    e.preventDefault();
                    this._isDateSelection = true;
                    this.focus();
                }
            }
        }
    });

    return DatetimeEditorView;
});
