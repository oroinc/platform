define(function(require) {
    'use strict';

    /**
     * Date cell content editor
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Mapped by frontend type
     *       {column-name-1}:
     *         frontend_type: date
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/date-editor-view
     *             view_options:
     *               css_class_name: '<class-name>'
     *               datePickerOptions:
     *                 altFormat: 'yy-mm-dd'
     *                 changeMonth: true
     *                 changeYear: true
     *                 yearRange: '-80:+1'
     *                 showButtonPanel: true
     *           validation_rules:
     *             NotBlank: true
     *           save_api_accessor:
     *               route: '<route>'
     *               query_parameter_names:
     *                  - '<parameter1>'
     *                  - '<parameter2>'
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for the date HTML input element
     * inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://api.jqueryui.com/datepicker/)
     * inline_editing.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {Object} options.dateInputAttrs - Attributes for date HTML input element
     * @param {Object} options.datePickerOptions - See [documentation here](http://api.jqueryui.com/datepicker/)
     * @param {string} options.value - initial value of edited field
     *
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports DateEditorView
     */
    var DateEditorView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var moment = require('moment');
    var datetimeFormatter = require('orolocale/js/formatter/datetime');
    var TextEditorView = require('./text-editor-view');
    var DatepickerView = require('oroui/js/app/views/datepicker/datepicker-view');

    DateEditorView = TextEditorView.extend(/** @exports DateEditorView.prototype */{
        className: 'date-editor',
        inputType: 'date',
        view: DatepickerView,

        DROPDOWN_BELOW_INPUT_CSS_CLASS: 'dropdown-below-input',
        DEFAULT_OPTIONS: {
            dateInputAttrs: {
                'placeholder': __('oro.form.choose_date'),
                'name': 'date',
                'autocomplete': 'off',
                'data-validation': JSON.stringify({Date: {}})
            },
            datePickerOptions: {
                altFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '-80:+1',
                showButtonPanel: true
            }
        },

        events: {
            'blur .hasDatepicker': 'onDatePickerBlur',
            'datepicker:dialogReposition .hasDatepicker': 'onDropdownReposition'
        },

        format: datetimeFormatter.getBackendDateFormat(),

        /**
         * @inheritDoc
         */
        constructor: function DateEditorView() {
            DateEditorView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            DateEditorView.__super__.render.call(this);
            var $input;
            var View = this.view;
            this.view = new View(this.getViewOptions());
            if (this.options.value) {
                this.setFormState(this.options.value);
            }
            this.view.getDatePickerWidget().on('mousedown' + this.eventNamespace(), _.bind(function() {
                this._isDateSelection = true;
            }, this));
            // fix enter behaviour
            $input = this.$('.hasDatepicker');
            $input.bindFirst('keydown' + this.eventNamespace(),
                _.bind(this.onGenericEnterKeydown, this));
            // fix esc behaviour
            $input.on('keydown' + this.eventNamespace(), _.bind(this.onGenericEscapeKeydown, this));
            // fix arrows behaviour
            $input.on('keydown' + this.eventNamespace(), _.bind(this.onGenericArrowKeydown, this));
        },

        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE) {
                // there is no other way to get if datepicker is visible
                if ($('#ui-datepicker-div').is(':visible')) {
                    if (!this.isChanged()) {
                        DateEditorView.__super__.onGenericEnterKeydown.apply(this, arguments);
                    } else {
                        this.$('.hasDatepicker').datepicker('hide');
                    }
                } else {
                    DateEditorView.__super__.onGenericEnterKeydown.apply(this, arguments);
                }
            }
        },

        onGenericEscapeKeydown: function(e) {
            if (e.keyCode === this.ESCAPE_KEY_CODE) {
                // there is no other way to get if datepicker is visible
                if ($('#ui-datepicker-div').is(':visible')) {
                    this.$('.hasDatepicker').datepicker('hide');
                } else {
                    DateEditorView.__super__.onGenericEscapeKeydown.apply(this, arguments);
                }
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this._isFocused = false;
            this.$('.hasDatepicker').off(this.eventNamespace());
            this.view.getDatePickerWidget().off(this.eventNamespace());
            this.view.dispose();
            DateEditorView.__super__.dispose.call(this);
        },

        /**
         * Prepares and returns editor sub-view options
         *
         * @returns {Object}
         */
        getViewOptions: function() {
            return $.extend(true, {}, this.DEFAULT_OPTIONS,
                _.pick(this.options, ['dateInputAttrs', 'datePickerOptions']), {
                    el: this.$('input[name=value]')
                });
        },

        focus: function() {
            this.$('input.hasDatepicker').setCursorToEnd().focus();
        },

        onFocusout: function(e) {
            if (this._isFocused && this._isDateSelection) {
                delete this._isDateSelection;
                this.focus();
            } else {
                DateEditorView.__super__.onFocusout.call(this, e);
            }
        },

        parseRawValue: function(value) {
            try {
                return datetimeFormatter.getMomentForBackendDate(value);
            } catch (e) {
                try {
                    return datetimeFormatter.getMomentForBackendDateTime(value);
                } catch (e2) {
                    return null;
                }
            }
        },

        formatRawValue: function(value) {
            value = this.parseRawValue(value);
            if (value === null) {
                return '';
            }
            return value.format(this.format);
        },

        getValue: function() {
            var raw = this.$('input[name=value]').val();
            return !raw ? null : moment.utc(raw, this.format);
        },

        isChanged: function() {
            var value = this.getValue();
            var modelValue = this.getModelValue();
            if (value !== null && modelValue !== null) {
                return value.diff(modelValue);
            }
            return value !== modelValue;
        },

        onDatePickerBlur: function() {
            this.toggleDropdownBelowClass(false);
        },

        onDropdownReposition: function(e, position) {
            this.toggleDropdownBelowClass(position === 'below');
        },

        toggleDropdownBelowClass: function(stateVal) {
            this.$el.toggleClass(this.DROPDOWN_BELOW_INPUT_CSS_CLASS, stateVal);
        }
    });

    return DateEditorView;
});
