/** @lends TextEditorView */
define(function(require) {
    'use strict';

    /**
     * Text cell content editor. This view is used by default (if no frontend type has been specified).
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
     *         frontend_type: string
     *       # Sample 2. Mapped by frontend type and placeholder specified
     *       {column-name-2}:
     *         frontend_type: string
     *         inline_editing:
     *           editor:
     *             view_options:
     *               placeholder: '<placeholder>'
     *       # Sample 3. Full configuration
     *       {column-name-3}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/text-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: ~
     *             Length:
     *               min: 3
     *               max: 255
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder translation key for an empty element
     * inline_editing.editor.view_options.placeholder_raw  | Optional. Raw placeholder value
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.fieldName - Field name to edit in model
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     *
     * @augments BaseView
     * @exports TextEditorView
     */
    var TextEditorView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    TextEditorView = BaseView.extend(/** @exports TextEditorView.prototype */{
        autoRender: true,
        tagName: 'form',
        template: require('tpl!../../../../templates/text-editor.html'),
        className: 'text-editor',
        inputType: 'text',
        events: {
            'change input[name=value]': 'onChange',
            'keyup input[name=value]': 'onChange',
            'mousedown': 'onMousedown',
            'click [data-action]': 'rethrowAction',
            'keydown input[name=value]': 'onGenericKeydown',
            'keydown': 'rethrowEvent',
            'keypress': 'rethrowEvent',
            'keyup': 'rethrowEvent',
            'focusin': 'onFocusin',
            'focusout': 'onFocusout'
        },

        TAB_KEY_CODE: 9,
        ENTER_KEY_CODE: 13,
        ESCAPE_KEY_CODE: 27,

        /**
         * Arrow codes
         */
        ARROW_LEFT_KEY_CODE: 37,
        ARROW_TOP_KEY_CODE: 38,
        ARROW_RIGHT_KEY_CODE: 39,
        ARROW_BOTTOM_KEY_CODE: 40,

        /**
         * Internal focus tracking variable
         * @protected
         */
        _isFocused: false,

        constructor: function(options) {
            // className adjustment cannot be done in initialize()
            if (options.className) {
                options.className += ' ' + _.result(this, 'className');
            }
            TextEditorView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            this.options = options;
            _.extend(this, _.pick(options, ['fieldName', 'placeholder', 'placeholder_raw', 'validationRules']));
            _.defaults(this, {
                validationRules: {}
            });
            TextEditorView.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(document).off(this.eventNamespace());
            TextEditorView.__super__.dispose.call(this);
        },

        /**
         * Returns placeholder
         * @returns {string}
         */
        getPlaceholder: function(emptyValue) {
            if (emptyValue === void 0) {
                emptyValue = '';
            }
            return this.placeholderRaw !== void 0 ? this.placeholderRaw :
                (this.placeholder !== void 0 ? __(this.placeholder) : emptyValue);
        },

        getTemplateData: function() {
            var data = {};
            data.inputType = this.inputType;
            data.data = this.model.toJSON();
            data.fieldName = this.fieldName;
            data.value = this.formatRawValue(this.getRawModelValue());
            data.placeholder = this.getPlaceholder();
            return data;
        },

        render: function() {
            TextEditorView.__super__.render.call(this);
            this.$el.addClass(_.result(this, 'className'));
            this.validator = this.$el.validate({
                submitHandler: _.bind(function(form, e) {
                    if (e && e.preventDefault) {
                        e.preventDefault();
                    }
                    this.trigger('saveAction');
                }, this),
                errorPlacement: function(error, element) {
                    error.appendTo($(element).closest('.inline-editor-wrapper'));
                },
                rules: {
                    value: $.validator.filterUnsupportedValidators(this.getValidationRules())
                }
            });
            if (this.options.value) {
                this.setFormState(this.options.value);
            }
            this.onChange();
        },

        /**
         * Shows backend validation errors
         *
         * @param {Object} backendErrors map of field name to its error
         */
        showBackendErrors: function(backendErrors) {
            this.validator.showErrors(backendErrors);
        },

        /**
         * Reads state of form (map of element name to its value)
         *
         * @return {Object}
         */
        getFormState: function() {
            return this.$el.formFieldValues();
        },

        /**
         * Set values to form elements
         *
         * @param {Object} value map of element name to its value
         */
        setFormState: function(value) {
            this.$el.formFieldValues(value);
        },

        /**
         * Places focus on the editor
         *
         * @param {boolean} atEnd - Usefull for multi input editors. Specifies which input should be focused: first
         *                         or last
         */
        focus: function(atEnd) {
            this.$('input[name=value]').setCursorToEnd().focus();
        },

        /**
         * Handles focusin event
         *
         * @param {jQuery.Event} e
         */
        onFocusin: function(e) {
            if (!this._isFocused) {
                this._isFocused = true;
                this.trigger('focus');
            }
        },

        /**
         * Handles focusout event
         *
         * @param {jQuery.Event} e
         */
        onFocusout: function(e) {
            if (!this._isSelected) {
                this.blur();
            } else {
                delete this._isSelected;
            }
        },

        /**
         * Handles mousedown event
         *
         * @param {jQuery.Event} e
         */
        onMousedown: function(e) {
            this._isSelected = true;
        },

        /**
         * Turn view into blur
         */
        blur: function() {
            if (this._isFocused) {
                this._isFocused = false;
                this.trigger('blur');
            }
        },

        /**
         * Prepares validation rules for usage
         *
         * @returns {Object}
         */
        getValidationRules: function() {
            return this.validationRules;
        },

        /**
         * Reads proper model's field value
         *
         * @return {*}
         */
        getRawModelValue: function() {
            return this.model.get(this.fieldName);
        },

        /**
         * Converts model value to the format that can be passed to a template as field value
         *
         * @param {*} value
         * @return {string}
         */
        formatRawValue: function(value) {
            return this.parseRawValue(value);
        },

        /**
         * Parses value that is stored in model
         *
         * @param {*} value
         * @return {*}
         */
        parseRawValue: function(value) {
            return value ? value : '';
        },

        /**
         * Returns the raw model value
         *
         * @returns {string}
         */
        getModelValue: function() {
            return this.parseRawValue(this.getRawModelValue());
        },

        /**
         * Returns the current value after user edit
         *
         * @returns {string}
         */
        getValue: function() {
            return this.$('input[name=value]').val();
        },

        /**
         * Generic handler for buttons which allows to notify overlaying component about some user action.
         * Any button with 'data-action' attribute will rethrow the action to the inline editing plugin.
         *
         * Available actions:
         * - save
         * - cancel
         * - saveAndEditNext
         * - saveAndEditPrev
         * - cancelAndEditNext
         * - cancelAndEditPrev
         *
         * Sample usage:
         * ``` html
         *  <button data-action="cancelAndEditNext">Skip and Go Next</button>
         * ```
         *
         * @returns {string}
         */
        rethrowAction: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            this.trigger($(e.currentTarget).attr('data-action') + 'Action');
        },

        /**
         * Generic handler for DOM events. Used on form to allow processing that events outside view.
         */
        rethrowEvent: function(e) {
            this.trigger(e.type, e, this);
        },

        /**
         * Returns true if the user has changed the value
         *
         * @returns {boolean}
         */
        isChanged: function() {
            return this.getValue() !== this.getModelValue();
        },

        /**
         * Returns true if the user entered valid data
         *
         * @returns {boolean}
         */
        isValid: function() {
            return this.validator.form();
        },

        /**
         * Change handler. In this realization, it tracks a submit button disabled attribute
         */
        onChange: function() {
            if (!this.isChanged()) {
                this.$('[type=submit]').attr('disabled', 'disabled');
            } else {
                this.$('[type=submit]').removeAttr('disabled');
            }
            this.trigger('change');
        },

        /**
         * Refers keydown action to proper action handler
         *
         * @param e
         */
        onGenericKeydown: function(e) {
            this.onGenericEnterKeydown(e);
            this.onGenericTabKeydown(e);
            this.onGenericArrowKeydown(e);
        },

        /**
         * Generic keydown handler, which handles ENTER
         *
         * @param {$.Event} e
         */
        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE) {
                var postfix = e.shiftKey ? 'AndEditPrevRow' : 'AndEditNextRow';
                if (e.ctrlKey) {
                    this.trigger('saveAndExitAction');
                } else {
                    if (this.isChanged()) {
                        if (this.validator.form()) {
                            this.trigger('save' + postfix + 'Action');
                        } else {
                            this.focus();
                        }
                    } else {
                        this.trigger('cancel' + postfix + 'Action');
                    }
                }
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler, which handles TAB
         *
         * @param {$.Event} e
         */
        onGenericTabKeydown: function(e) {
            if (e.keyCode === this.TAB_KEY_CODE) {
                var postfix = e.shiftKey ? 'AndEditPrev' : 'AndEditNext';
                if (this.isChanged()) {
                    if (this.isValid()) {
                        this.trigger('save' + postfix + 'Action');
                    } else {
                        this.focus();
                    }
                } else {
                    this.trigger('cancel' + postfix + 'Action');
                }
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler, which handles ESCAPE
         *
         * @param {$.Event} e
         */
        onGenericEscapeKeydown: function(e) {
            if (e.keyCode === this.ESCAPE_KEY_CODE) {
                this.trigger('cancelAction');
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler, which handles ARROWS
         *
         * @param {$.Event} e
         */
        onGenericArrowKeydown: function(e) {
            if (e.altKey) {
                var postfix;
                switch (e.keyCode) {
                    case this.ARROW_LEFT_KEY_CODE:
                        postfix = 'AndEditPrev';
                        break;
                    case this.ARROW_RIGHT_KEY_CODE:
                        postfix = 'AndEditNext';
                        break;
                    case this.ARROW_BOTTOM_KEY_CODE:
                        postfix = 'AndEditNextRow';
                        break;
                    case this.ARROW_TOP_KEY_CODE:
                        postfix = 'AndEditPrevRow';
                        break;
                }
                if (postfix) {
                    if (this.isChanged()) {
                        if (this.isValid()) {
                            this.trigger('save' + postfix + 'Action');
                        } else {
                            this.focus();
                        }
                    } else {
                        this.trigger('cancel' + postfix + 'Action');
                    }
                    e.stopPropagation();
                    e.preventDefault();
                }
            }
        },

        /**
         * Returns data which should be sent to the server
         *
         * @returns {Object}
         */
        getServerUpdateData: function() {
            var data = {};
            data[this.fieldName] = this.getValue();
            return data;
        },

        /**
         * Returns data for the model update
         *
         * @returns {Object}
         */
        getModelUpdateData: function() {
            return this.getServerUpdateData();
        }
    });

    return TextEditorView;
});
