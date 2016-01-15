/** @lends DateEditorView */
define(function(require) {
    'use strict';

    /**
     * Date cell content editor
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
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.view_options.dateInputAttrs   | Optional. Attributes for the date HTML input element
     * inline_editing.editor.view_options.datePickerOptions| Optional. See [documentation here](http://goo.gl/pddxZU)
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
                placeholder: __('oro.form.choose_date'),
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

        listen: {
            'inlineEditor:focus mediator': 'onInlineEditorFocus'
        },

        format: datetimeFormatter.backendFormats.date,

        render: function() {
            DateEditorView.__super__.render.call(this);
            var View = this.view;
            this.view = new View(this.getViewOptions());
            // fix enter behaviour
            this.$('.hasDatepicker').bindFirst('keydown' + this.eventNamespace(),
                _.bind(this.onGenericEnterKeydown, this));
            // fix esc behaviour
            this.$('.hasDatepicker').on('keydown' + this.eventNamespace(), _.bind(this.onGenericEscapeKeydown, this));
            // fix arrows behaviour
            this.$('.hasDatepicker').on('keydown' + this.eventNamespace(), _.bind(this.onGenericArrowKeydown, this));
            this.attachFocusTracking();
        },

        onInlineEditorFocus: function(view) {
            if (view === this) {
                return;
            }
            this.$('.hasDatepicker').datepicker('hide');
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
            this.$('.hasDatepicker').off(this.eventNamespace());
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

        getModelValue: function() {
            var raw = this.model.get(this.fieldName);
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
        },

        /**
         * Attaches focus tracking
         */
        attachFocusTracking: function() {
            var blurTestIntervalId = -1;
            var _isFocused = this.isFocused();
            this.$('.hasDatepicker').off('.attachFocusTracking');
            if (this.$('.datepicker-focusser').length === 0) {
                this.$el.append('<input class="datepicker-focusser"/>');
            }

            this.$('.hasDatepicker').on('change', _.bind(function() {
                if (document.activeElement !== this.$('.hasDatepicker').get(0)) {
                    this.$('.datepicker-focusser').focus();
                }
            }, this));

            var checkBlur = _.bind(function() {
                if (!_isFocused) {
                    return;
                }
                clearInterval(blurTestIntervalId);
                blurTestIntervalId = setInterval(_.bind(function() {
                    if (this.disposed || !this.isFocused()) {
                        if (!this.disposed) {
                            this.trigger('blur');
                        }
                        _isFocused = false;
                        clearInterval(blurTestIntervalId);
                    }
                }, this), 25);
            }, this);
            this.$('.hasDatepicker').datepicker('option', 'onClose', checkBlur);
            this.$('.hasDatepicker, .datepicker-focusser').on('blur.attachFocusTracking', checkBlur);

            this.$('.hasDatepicker').on('focus.attachFocusTracking', _.bind(function() {
                if (!_isFocused) {
                    this.trigger('focus');
                    _isFocused = true;
                }
            }, this));
        },

        /**
         * Returns true if element is focused
         *
         * @returns {boolean}
         */
        isFocused: function() {
            return document.activeElement === this.$('.hasDatepicker').get(0) ||
                   document.activeElement === this.$('.datepicker-focusser').get(0) ||
                   $('#ui-datepicker-div').is(':visible');
        }
    });

    return DateEditorView;
});
