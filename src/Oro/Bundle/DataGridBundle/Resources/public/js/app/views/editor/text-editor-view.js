/** @lends TextEditorView */
define(function(require) {
    'use strict';

    /**
     * Text cell content editor. this view is used by default (when no frontend type specified).
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
     *             view: orodatagrid/js/app/views/editor/text-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for empty element
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container.
     * @param {Object} options.model - current row model
     * @param {Backgrid.Cell} options.cell - current datagrid cell
     * @param {Backgrid.Column} options.column - current datagrid column
     * @param {string} options.placeholder - placeholder for empty element
     * @param {Object} options.validationRules - validation rules in form applicable to jQuery.validate
     *
     * @augments BaseView
     * @exports TextEditorView
     */
    var TextEditorView;
    var _ = require('underscore');
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
            'click [data-action]': 'rethrowAction'
        },

        TAB_KEY_CODE: 9,
        ENTER_KEY_CODE: 13,
        ESCAPE_KEY_CODE: 27,

        initialize: function(options) {
            this.options = options;
            this.cell = options.cell;
            this.column = options.column;
            this.placeholder = options.placeholder;
            this.validationRules = options.validationRules || {};
            $(document).on('keydown' + this.eventNamespace(), _.bind(this.onKeyDown, this));
            TextEditorView.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(document).off(this.eventNamespace());
            TextEditorView.__super__.dispose.call(this);
        },

        getTemplateData: function() {
            var data = {};
            data.inputType = this.inputType;
            data.data = this.model.toJSON();
            data.column = this.column.toJSON();
            data.value = this.getFormattedValue();
            data.placeholder = this.placeholder || '';
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
                    error.appendTo(element.closest('.inline-editor-wrapper'));
                },
                rules: {
                    value: this.getValidationRules()
                }
            });
            this.onChange();
        },

        /**
         * Places focus on editor
         *
         * @param {boolean} atEnd - Usefull for multi inputs editors. Specifies which input should be focused first
         *                         or last
         */
        focus: function(atEnd) {
            this.$('input[name=value]').setCursorToEnd().focus();
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
         * Formats and returns model value before it will be rendered
         *
         * @returns {string}
         */
        getFormattedValue: function() {
            return this.getModelValue();
        },

        /**
         * Returns raw model value
         *
         * @returns {string}
         */
        getModelValue: function() {
            var raw = this.model.get(this.column.get('name'));
            return raw ? raw : '';
        },

        /**
         * Returns current user edited value
         *
         * @returns {string}
         */
        getValue: function() {
            return this.$('input[name=value]').val();
        },

        /**
         * Generic handler for buttons which allows to notify overlaying component about some user action.
         * Any button with 'data-action' attribute will rethrow action to inline editing plugin.
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
         * Returns true if user has changed value
         *
         * @returns {boolean}
         */
        isChanged: function() {
            return this.getValue() !== this.getModelValue();
        },

        /**
         * Change handler. In this realization - tracks submit button disabled attribute
         */
        onChange: function() {
            if (!this.isChanged()) {
                this.$('[type=submit]').attr('disabled', 'disabled');
            } else {
                this.$('[type=submit]').removeAttr('disabled');
            }
        },

        /**
         * Keydown handler for entire document
         *
         * @param {$.Event} e
         */
        onKeyDown: function(e) {
            switch (e.keyCode) {
                case this.TAB_KEY_CODE:
                    this.onGenericTabKeydown(e);
                    break;
                case this.ENTER_KEY_CODE:
                    this.onGenericEnterKeydown(e);
                    break;
                case this.ESCAPE_KEY_CODE:
                    this.onGenericEscapeKeydown(e);
                    break;
            }
        },

        /**
         * Generic keydown handler which handles ENTER
         *
         * @param {$.Event} e
         */
        onGenericEnterKeydown: function(e) {
            if (e.keyCode === this.ENTER_KEY_CODE) {
                if (this.isChanged()) {
                    if (this.validator.form()) {
                        this.trigger('saveAction');
                    } else {
                        this.focus();
                    }
                } else {
                    this.trigger('cancelAction');
                }
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        },

        /**
         * Generic keydown handler which handles TAB
         *
         * @param {$.Event} e
         */
        onGenericTabKeydown: function(e) {
            if (e.keyCode === this.TAB_KEY_CODE) {
                var postfix = e.shiftKey ? 'AndEditPrev' : 'AndEditNext';
                if (this.isChanged()) {
                    if (this.validator.form()) {
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
         * Generic keydown handler which handles ESCAPE
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
         * Returns data which should be sent to server
         *
         * @returns {Object}
         */
        getServerUpdateData: function() {
            var data = {};
            data[this.column.get('name')] = this.getValue();
            return data;
        },

        /**
         * Returns data to update model
         *
         * @returns {Object}
         */
        getModelUpdateData: function() {
            return this.getServerUpdateData();
        }
    });

    return TextEditorView;
});
