/** @lends MultiCheckboxEditorView */
define(function(require) {
    'use strict';

    /**
     * Multi-select content editor. Please note that it requires column data format
     * corresponding to multi-select-cell.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrid:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Full configuration
     *       {column-name-1}:
     *         inline_editing:
     *           editor:
     *             view: orodatagrid/js/app/views/editor/multi-checkbox-editor-view
     *             view_options:
     *               css_class_name: '<class-name>'
     *           validation_rules:
     *             NotBlank: true
     * ```
     *
     * ### Options in yml:
     *
     * Column option name                                  | Description
     * :---------------------------------------------------|:-----------
     * inline_editing.editor.view_options.css_class_name   | Optional. Additional css class name for editor view DOM el
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](https://goo.gl/j9dj4Y)
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {Backgrid.Cell} options.cell - Current datagrid cell
     * @param {Backgrid.Column} options.column - Current datagrid column
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](https://goo.gl/j9dj4Y)
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports MultiSelectEditorView
     */

    var MultiCheckboxEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    require('jquery.multiselect');
    require('jquery.multiselect.filter');

    MultiCheckboxEditorView = SelectEditorView.extend(/** @exports MultiCheckboxEditorView.prototype */{
        className: 'multi-checkbox-editor',
        template: require('tpl!orodatagrid/templates/multi-checkbox-editor.html'),

        /**
         * Jquery object that wraps select DOM element with initialized multiselect plugin
         *
         * @property
         */
        multiselect: null,

        events: {
            'change select': 'onChange',
            'click [data-action]': 'rethrowAction',
            'updatePosition': 'onUpdatePosition'
        },

        listen: {
            'change:visibility': 'onShow'
        },

        onShow: function() {
            this.multiselect = this.$('select').multiselect({
                autoOpen: true,
                classes: this.className,
                header: '',
                height: 'auto',
                position: {
                    my: 'left top-36',
                    at: 'left bottom',
                    of: this.$el
                },
                beforeclose: function() {
                    return false;
                }
            }).multiselectfilter({
                label: '',
                placeholder: '',
                autoReset: true
            });
        },

        onUpdatePosition: function() {
            if (this.multiselect) {
                this.multiselect.multiselect('updatePos');
            }
        },

        getModelValue: function() {
            var value = this.model.get(this.column.get('name'));
            if (_.isString(value)) {
                value = JSON.parse(value);
            } else if (_.isArray(value)) {
                value = _.filter(value, function(item) {
                    return item !== '';
                });
            } else if (_.isNull(value) || value === void 0) {
                return [];
            }
            return value;
        },

        getValue: function() {
            var value = this.$('select').val();
            return _.isArray(value) ? value : [];
        },

        getTemplateData: function() {
            var data = MultiCheckboxEditorView.__super__.getTemplateData.call(this);
            _.extend(data, {
                options: this.availableChoices,
                selected: this.getModelValue()
            });
            return data;
        },

        isChanged: function() {
            var val = this.getValue();
            var old = this.getModelValue();
            if (!_.isArray(old)) {
                old = old === 0 || old ? [old] : [];
            }
            return val.length !== old.length || _.difference(val, old).length > 0;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.multiselect) {
                this.multiselect.multiselect('destroy');
                this.multiselect = null;
            }
            MultiCheckboxEditorView.__super__.dispose.call(this);
        }
    });

    return MultiCheckboxEditorView;
});
