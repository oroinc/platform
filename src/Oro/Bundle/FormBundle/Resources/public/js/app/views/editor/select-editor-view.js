/** @lends SelectEditorView */
define(function(require) {
    'use strict';

    /**
     * Select cell content editor. The cell value should be a value field.
     * The grid will render a corresponding label from the `options.choices` map.
     * The editor will use the same mapping
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
     *         frontend_type: select
     *         choices: # required
     *           key-1: First
     *           key-2: Second
     *       # Sample 2. Full configuration
     *       {column-name-2}:
     *         choices: # required
     *           key-1: First
     *           key-2: Second
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: ~
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:---------------------------------------
     * choices                                             | Key-value set of available choices
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
     * @param {Object} options.choices - Key-value set of available choices
     *
     * @augments [TextEditorView](./text-editor-view.md)
     * @exports SelectEditorView
     */
    var SelectEditorView;
    var TextEditorView = require('./text-editor-view');
    var $ = require('jquery');
    var _ = require('underscore');
    require('jquery.select2');

    SelectEditorView = TextEditorView.extend(/** @exports SelectEditorView.prototype */{
        className: 'select-editor',

        SELECTED_ITEMS_H_MARGIN_BETWEEN: 5,
        SELECTED_ITEMS_V_MARGIN_BETWEEN: 6,
        SELECTED_ITEMS_H_INCREMENT: 2,

        events: {
            'updatePosition': 'updatePosition'
        },

        initialize: function(options) {
            SelectEditorView.__super__.initialize.apply(this, arguments);
            this.availableChoices = this.getAvailableOptions(options);
            this.prestine = true;
        },

        getAvailableOptions: function(options) {
            var choices = this.options.choices;
            var result = [];
            for (var id in choices) {
                if (choices.hasOwnProperty(id)) {
                    result.push({
                        id: id,
                        text: choices[id]
                    });
                }
            }
            return result;
        },

        render: function() {
            var _this = this;
            SelectEditorView.__super__.render.call(this);
            this.$('input[name=value]').select2(this.getSelect2Options());
            // select2 stops propagation of keydown event if key === ENTER or TAB
            // need to restore this functionality
            this.$('.select2-focusser').on('keydown' + this.eventNamespace(), function(e) {
                _this.onGenericEnterKeydown(e);
                _this.onGenericTabKeydown(e);
                _this.onGenericArrowKeydown(e);
            });

            // must prevent selection on TAB
            this.$('input.select2-input').bindFirst('keydown' + this.eventNamespace(), function(e) {
                var prestine = _this.prestine;
                _this.prestine = false;
                switch (e.keyCode) {
                    case _this.ENTER_KEY_CODE:
                        if (prestine  && !_this.getModelValue()) {
                            e.stopImmediatePropagation();
                            e.preventDefault();
                            _this.$('input[name=value]').select2('close');
                            _this.onGenericEnterKeydown(e);
                        }
                        break;
                    case _this.TAB_KEY_CODE:
                        e.stopImmediatePropagation();
                        e.preventDefault();
                        _this.$('input[name=value]').select2('close');
                        _this.onGenericTabKeydown(e);
                        break;
                }
                _this.onGenericArrowKeydown(e);
            });
            this.$('input.select2-input').bind('keydown' + this.eventNamespace(), function(e) {
                // Due to this view can be already disposed in bound first handler,
                // we have to check if it's disposed
                if (!_this.disposed && !_this.isChanged()) {
                    SelectEditorView.__super__.onGenericEnterKeydown.call(_this, e);
                }
            });
        },

        updatePosition: function() {
            this.$('input[name=value]').select2('positionDropdown');
        },

        /**
         * Prepares and returns Select2 options
         *
         * @returns {Object}
         */
        getSelect2Options: function() {
            return {
                placeholder: this.getPlaceholder(' '),
                allowClear: !this.getValidationRules().NotBlank,
                selectOnBlur: false,
                openOnEnter: false,
                data: {results: this.availableChoices}
            };
        },

        /**
         * Returns Select2 data from corresponding element
         *
         * @returns {Object}
         */
        getSelect2Data: function() {
            return this.$('.select2-choice').data('select2-data');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.select2-focusser').off(this.eventNamespace());
            this.$('input.select2-input').off(this.eventNamespace());
            this.$('input[name=value]').select2('destroy');
            // due to bug in select2
            $('body > .select2-drop-mask, body > .select2-drop').remove();
            SelectEditorView.__super__.dispose.call(this);
        },

        focus: function() {
            var isFocused = this.isFocused();
            this.$('input[name=value]').select2('open');
            if (!isFocused) {
                // trigger custom focus event as select2 doesn't trigger 'select2-focus' when focused manually
                this.trigger('focus');
                this._isFocused = true;
            }
        },

        isChanged: function() {
            // current value is always string
            // btw model value could be an number
            return this.getValue() !== String(this.getModelValue());
        },

        /**
         * Internal focus tracking variable
         * @protected
         */
        _isFocused: false,

        /**
         * Attaches focus tracking
         */
        attachFocusTracking: function() {
            var _this = this;
            this._isFocused = this.isFocused();
            this.$('input[name=value]').on('select2-focus', function() {
                if (!_this._isFocused) {
                    _this._isFocused = true;
                    _this.trigger('focus');
                }
            });
            this.$('input[name=value]').on('select2-blur', function() {
                // let select2 time to work due to bugs
                _.defer(function() {
                    if (_this.$el && !_this.isFocused()) {
                        if (_this._isFocused) {
                            _this._isFocused = false;
                            _this.trigger('blur');
                        }
                    }
                });
            });
        },

        /**
         * Returns true if element is focused
         *
         * @returns {boolean}
         */
        isFocused: function() {
            return this.$('.select2-container-active').length;
        }
    }, {
        processMetadata: function(columnMetadata) {
            if (_.isUndefined(columnMetadata.choices)) {
                throw new Error('`choices` is required option');
            }
            if (!columnMetadata.inline_editing.editor.view_options) {
                columnMetadata.inline_editing.editor.view_options = {};
            }
            columnMetadata.inline_editing.editor.view_options.choices = columnMetadata.choices;
        }
    });

    return SelectEditorView;
});
