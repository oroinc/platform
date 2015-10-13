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
     *             view: orodatagrid/js/app/views/editor/select-editor-view
     *             view_options:
     *               placeholder: '<placeholder>'
     *               css_class_name: '<class-name>'
     *           validationRules:
     *             # jQuery.validate configuration
     *             required: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:---------------------------------------
     * choices                                             | Key-value set of available choices
     * inline_editing.editor.view_options.placeholder      | Optional. Placeholder for an empty element
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.validationRules               | Optional. The client side validation rules
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder for an empty element
     * @param {Object} options.validationRules - Validation rules in a form applicable for jQuery.validate
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

        initialize: function(options) {
            this.availableChoices = this.getAvailableOptions(options);
            SelectEditorView.__super__.initialize.apply(this, arguments);
        },

        getAvailableOptions: function(options) {
            var choices = options.column.get('metadata').choices;
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
            this.$('.select2-focusser').on('keydown' + this.eventNamespace(),
                _.bind(this.onGenericEnterKeydown, this));
            this.$('.select2-focusser').on('keydown' + this.eventNamespace(),
                _.bind(this.onGenericTabKeydown, this));
            this.$('.select2-focusser').on('keydown' + this.eventNamespace(),
                _.bind(this.onGenericArrowKeydown, this));

            // must prevent selection on TAB
            this.$('input.select2-input').bindFirst('keydown' + this.eventNamespace(), function(e) {
                if (e.keyCode === _this.TAB_KEY_CODE) {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    _this.$('input[name=value]').select2('close');
                    _this.onGenericTabKeydown(e);
                }
                _this.onGenericArrowKeydown(e);
            });
        },

        /**
         * Prepares and returns Select2 options
         *
         * @returns {Object}
         */
        getSelect2Options: function() {
            return {
                placeholder: this.placeholder || ' ',
                allowClear: true,
                selectOnBlur: false,
                openOnEnter: false,
                data: {results: this.availableChoices}
            };
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$('.select2-focusser').off(this.eventNamespace());
            this.$('input.select2-input').off(this.eventNamespace());
            this.$('input[name=value]').select2('destroy');
            // due to bug in select2
            $('body > .select2-drop-mask').remove();
            SelectEditorView.__super__.dispose.call(this);
        },

        focus: function() {
            this.$('input[name=value]').select2('open');
        },

        isChanged: function() {
            // current value is always string
            // btw model value could be an number
            return this.getValue() !== String(this.getModelValue());
        }
    });

    return SelectEditorView;
});
