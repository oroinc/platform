define(function(require) {
    'use strict';

    const SelectEditorView = require('./select-editor-view');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const {Multiselect} = require('oroui/js/app/views/multiselect');
    const manageFocus = require('oroui/js/tools/manage-focus').default;

    /**
     * Multi-select content editor. Please note that it requires column data format
     * corresponding to multi-select-cell.
     *
     * ### Column configuration samples:
     * ``` yml
     * datagrids:
     *   {grid-uid}:
     *     inline_editing:
     *       enable: true
     *     # <grid configuration> goes here
     *     columns:
     *       # Sample 1. Full configuration
     *       {column-name-1}:
     *         inline_editing:
     *           editor:
     *             view: oroform/js/app/views/editor/multi-checkbox-editor-view
     *             view_options:
     *               css_class_name: '<class-name>'
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
     * inline_editing.editor.validation_rules | Optional. Validation rules. See [documentation](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * inline_editing.save_api_accessor                    | Optional. Sets accessor module, route, parameters etc.
     *
     * ### Constructor parameters
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.model - Current row model
     * @param {string} options.placeholder - Placeholder translation key for an empty element
     * @param {string} options.placeholder_raw - Raw placeholder value. It overrides placeholder translation key
     * @param {string} options.maximumSelectionLength - Maximum selection length
     * @param {Object} options.validationRules - Validation rules. See [documentation here](../reference/js_validation.md#conformity-server-side-validations-to-client-once)
     * @param {string} options.value - initial value of edited field
     *
     * @augments [SelectEditorView](./select-editor-view.md)
     * @exports MultiCheckboxEditorView
     */
    const MultiCheckboxEditorView = SelectEditorView.extend(/** @lends MultiCheckboxEditorView.prototype */{
        className: 'multi-checkbox-editor',
        template: require('tpl-loader!oroform/templates/editor/multi-checkbox-editor.html'),

        /**
         * Jquery object that wraps select DOM element with initialized multiselect plugin
         *
         * @property {Object}
         */
        multiselect: null,

        events: {
            'change select': 'onChange',
            'click [data-action]': 'rethrowAction',
            'click [data-role="apply"]': 'onApplyChanges'
        },

        listen: {
            'change:visibility': 'onShow'
        },

        /**
         * @inheritdoc
         */
        constructor: function MultiCheckboxEditorView(options) {
            MultiCheckboxEditorView.__super__.constructor.call(this, options);
        },

        onApplyChanges: function() {
            this.prestine = false;
        },

        onShow: function() {
            const multiselect = new Multiselect({
                autoRender: true,
                selectElement: this.$('select'),
                container: this.$el,
                enabledHeader: false,
                hideResetButton: true,
                placeholder: __('oro.form.inlineEditing.multi_checkbox_editor.filter.placeholder'),
                cssConfig: {
                    main: _.result(this, 'className')
                }
            });

            this.subview('multiselect', multiselect);

            manageFocus.focusTabbable(multiselect.$('[data-role="items"]'));

            multiselect.$('[data-role="items"] label')
                .bindFirst(`keydown${this.eventNamespace()}`, event => {
                    this.prestine = false;

                    switch (event.keyCode) {
                        case this.ENTER_KEY_CODE:
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            if (this.prestine) {
                                this.trigger('cancelAction');
                            }

                            this.onGenericEnterKeydown(event);
                            break;
                        case this.TAB_KEY_CODE:
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            if (this.prestine) {
                                this.trigger('cancelAction');
                            }

                            this.onGenericTabKeydown(event);
                            break;
                        case this.ESCAPE_KEY_CODE:
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            if (this.prestine) {
                                this.trigger('cancelAction');
                            }

                            this.onGenericEscapeKeydown(event);
                            break;
                    }

                    this.onGenericArrowKeydown(event);
                });

            multiselect.$('[data-role="search-input"]')
                .on(`keydown${this.eventNamespace()}`, event => {
                    this.prestine = false;

                    this.onGenericEnterKeydown(event);
                    this.onGenericTabKeydown(event);
                    this.onGenericArrowKeydown(event);
                    this.onGenericEscapeKeydown(event);
                });
        },

        parseRawValue: function(value) {
            if (_.isString(value)) {
                value = JSON.parse(value);
            } else if (Array.isArray(value)) {
                value = _.filter(value, function(item) {
                    return item !== '';
                });
            } else if (_.isNull(value) || value === void 0) {
                value = [];
            }
            return value;
        },

        getValue: function() {
            const value = this.$('select').val();
            return Array.isArray(value) ? value : [];
        },

        getTemplateData: function() {
            const data = MultiCheckboxEditorView.__super__.getTemplateData.call(this);
            _.extend(data, {
                options: this.availableChoices
            });
            return data;
        },

        isChanged: function() {
            const val = this.getValue();
            let old = this.getModelValue();
            if (!Array.isArray(old)) {
                old = old === 0 || old ? [old] : [];
            }
            return val.length !== old.length || _.difference(val, old).length > 0;
        }
    });

    return MultiCheckboxEditorView;
});
