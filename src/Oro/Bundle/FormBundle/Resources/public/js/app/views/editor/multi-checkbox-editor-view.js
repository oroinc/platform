define(function(require) {
    'use strict';

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

    var MultiCheckboxEditorView;
    var SelectEditorView = require('./select-editor-view');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    require('jquery.multiselect');
    require('jquery.multiselect.filter');

    MultiCheckboxEditorView = SelectEditorView.extend(/** @lends MultiCheckboxEditorView.prototype */{
        className: 'multi-checkbox-editor',
        template: require('tpl!oroform/templates/editor/multi-checkbox-editor.html'),

        /**
         * Jquery object that wraps select DOM element with initialized multiselect plugin
         *
         * @property {Object}
         */
        multiselect: null,

        events: {
            'change select': 'onChange',
            'click [data-action]': 'rethrowAction',
            'updatePosition': 'onUpdatePosition',
            'click [data-role="apply"]': 'onApplyChanges'
        },

        listen: {
            'change:visibility': 'onShow'
        },

        /**
         * @inheritDoc
         */
        constructor: function MultiCheckboxEditorView() {
            MultiCheckboxEditorView.__super__.constructor.apply(this, arguments);
        },

        onApplyChanges: function() {
            this.prestine = false;
            this.multiselect.multiselect('close');
        },

        onShow: function() {
            this.multiselect = this.$('select').multiselect({
                autoOpen: true,
                classes: _.result(this, 'className'),
                header: '',
                height: '',
                position: {
                    my: 'left top',
                    at: 'left top',
                    of: this.$el
                },
                outerTrigger: this.$('[data-role="apply"]'),
                beforeclose: function() {
                    if (this.prestine) {
                        this.trigger('cancelAction');
                    }
                }.bind(this)
            }).multiselectfilter({
                label: '',
                placeholder: __('oro.form.inlineEditing.multi_checkbox_editor.filter.placeholder'),
                autoReset: true
            });

            this.multiselect.multiselect('getMenu').find('label')
                .bindFirst('keydown' + this.eventNamespace(), function(event) {
                    this.prestine = false;

                    switch (event.keyCode) {
                        case this.ENTER_KEY_CODE:
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            this.multiselect.multiselect('close');

                            this.onGenericEnterKeydown(event);
                            break;
                        case this.TAB_KEY_CODE:
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            this.multiselect.multiselect('close');

                            this.onGenericTabKeydown(event);
                            break;
                        case this.ESCAPE_KEY_CODE:
                            event.stopImmediatePropagation();
                            event.preventDefault();

                            this.multiselect.multiselect('close');

                            this.onGenericEscapeKeydown(event);
                            break;
                    }

                    this.onGenericArrowKeydown(event);
                }.bind(this));

            this.multiselect.multiselectfilter('instance').input
                .on('keydown' + this.eventNamespace(), function(event) {
                    this.prestine = false;

                    this.onGenericEnterKeydown(event);
                    this.onGenericTabKeydown(event);
                    this.onGenericArrowKeydown(event);
                    this.onGenericEscapeKeydown(event);
                }.bind(this));
        },

        onUpdatePosition: function() {
            if (this.multiselect) {
                this.multiselect.multiselect('updatePos');
            }
        },

        parseRawValue: function(value) {
            if (_.isString(value)) {
                value = JSON.parse(value);
            } else if (_.isArray(value)) {
                value = _.filter(value, function(item) {
                    return item !== '';
                });
            } else if (_.isNull(value) || value === void 0) {
                value = [];
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
                options: this.availableChoices
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
