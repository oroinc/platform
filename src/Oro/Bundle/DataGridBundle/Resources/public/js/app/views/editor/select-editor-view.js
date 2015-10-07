/** @lends SelectEditorView */
define(function(require) {
    'use strict';

    /**
     * Text cell content editor
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - current row model
     * @param {Backgrid.Cell} options.cell - current datagrid cell
     * @param {Backgrid.Column} options.column - current datagrid column
     * @param {string} options.placeholder - placeholder for empty element
     * @param {Object} options.validationRules - validation rules in form applicable to jQuery.validate
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

            // must prevent selection on TAB
            this.$('input.select2-input').bindFirst('keydown' + this.eventNamespace(), function(e) {
                if (e.keyCode === _this.TAB_KEY_CODE) {
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    _this.$('input[name=value]').select2('close');
                    _this.onGenericTabKeydown(e);
                }
            });
        },

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
