define(function(require) {
    'use strict';

    var AttributeFormOptionEditView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var FieldChoiceView = require('oroentity/js/app/views/field-choice-view');
    var helper = require('oroworkflow/js/tools/workflow-helper');
    require('jquery.validate');

    AttributeFormOptionEditView = BaseView.extend({
        attributes: {
            'class': 'widget-content'
        },

        options: {
            template: null,
            data: {
                label: '',
                property_path: '',
                property_path_text: '',
                required: false
            },
            entity_field_template: null,
            entity: null,
            filterPreset: null,
            workflow: null,
            entityFieldsProvider: null
        },

        requiredOptions: ['workflow', 'entityFieldsProvider'],

        /**
         * @inheritDoc
         */
        constructor: function AttributeFormOptionEditView() {
            AttributeFormOptionEditView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options = options || {};
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(', '));
            }
            this.options = _.defaults(options, this.options);
            var template = this.options.template || $('#attribute-form-option-edit-template').html();
            this.template = _.template(template);

            this.entity_field_template = this.options.entity_field_template ||
                $('#entity-column-chain-template').html();
        },

        getFieldChoiceView: function() {
            return this.subview('field-choice');
        },

        onAdd: function() {
            var formData = helper.getFormData(this.form);

            formData.property_path = this.options.entityFieldsProvider.getPropertyPathByPath(formData.property_path);
            formData.required = formData.hasOwnProperty('required');

            this.resetForm();
            this.trigger('formOptionAdd', formData);
        },

        resetForm: function() {
            this.subview('field-choice').setValue('');
            this.form.find('[name=itemId]').val('');
            this.form.get(0).reset();
            this.submitBtn.html('<i class="fa-plus"></i> ' + __('Add'));
            this.resetBtn.addClass('hide');
        },

        initFieldChoiceView: function(container) {
            this.subview('field-choice', new FieldChoiceView({
                autoRender: true,
                el: container.find('[name="property_path"]'),
                entity: this.options.entity,
                filterPreset: this.options.filterPreset,
                allowSelectRelation: true,
                select2: {
                    placeholder: __('Choose field...')
                }
            }));
        },

        editRow: function(data) {
            this.fieldSelectorEl.inputWidget(
                'val',
                this.options.entityFieldsProvider.getPathByPropertyPathSafely(data.property_path)
            );
            this.form.find('[name=itemId]').val(data.itemId || '');
            this.labelEl.val(data.isSystemLabel ? '' : data.label);
            this.requiredEl.get(0).checked = data.required;
            this.submitBtn.html('<i class="fa-pencil-square-o"></i> ' + __('Update'));
            this.resetBtn.removeClass('hide');
        },

        render: function() {
            this._deferredRender();
            this.form = $(this.template(this.options.data)).filter('form');
            this.form.validate({
                submitHandler: _.bind(this.onAdd, this)
            });
            this.form.on('submit', function(e) {
                e.preventDefault();
            });
            this.initFieldChoiceView(this.form);

            this.submitBtn = this.form.find('[type=submit]');
            this.resetBtn = this.form.find('[type=reset]');
            this.fieldSelectorEl = this.form.find('[name=property_path]');
            this.labelEl = this.form.find('[name=label]');
            this.requiredEl = this.form.find('[name=required]');

            this.resetBtn.click(_.bind(this.resetForm, this));

            this.$el.append(this.form);
            // since we have no async operation right here but there is one in subview `deferredRender` promise
            // will be resolved with promise of subview
            this._resolveDeferredRender();
            return this;
        }
    });

    return AttributeFormOptionEditView;
});
