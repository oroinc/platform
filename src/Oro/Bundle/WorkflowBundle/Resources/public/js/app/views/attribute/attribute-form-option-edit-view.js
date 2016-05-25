define(function(require) {
    'use strict';

    var AttributeFormOptionEditView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var helper = require('oroworkflow/js/tools/workflow-helper');
    var __ = require('orotranslation/js/translator');
    require('oroentity/js/field-choice');
    require('jquery.validate');

    AttributeFormOptionEditView = BaseView.extend({
        attributes: {
            'class': 'widget-content'
        },

        options: {
            template: null,
            data: {
                'label': '',
                'property_path': '',
                'property_path_text': '',
                'required': false
            },
            entity_field_template: null,
            entity_select_el: null,
            workflow: null
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#attribute-form-option-edit-template').html();
            this.template = _.template(template);

            this.entity_field_template = this.options.entity_field_template ||
                $('#entity-column-chain-template').html();

            this.editViewId = null;
        },

        getFieldSelector: function() {
            return this.entityFieldSelectEl;
        },

        onAdd: function() {
            var formData = helper.getFormData(this.form);

            formData.property_path = this.options.workflow.getPropertyPathByFieldId(formData.property_path);
            formData.required = formData.hasOwnProperty('required');
            formData.view_id = this.editViewId;

            this.resetForm();
            this.trigger('formOptionAdd', formData);
        },

        resetForm: function() {
            this.editViewId = null;
            this.entityFieldSelectEl.inputWidget('val', '');
            this.form.get(0).reset();
            this.submitBtn.html('<i class="icon-plus"></i> ' + __('Add'));
            this.resetBtn.addClass('hide');
        },

        initFieldChoice: function(container) {
            this.entityFieldSelectEl = container.find('[name="property_path"]');
            this.entityFieldSelectEl.fieldChoice({
                fieldsLoaderSelector: this.options.entity_select_el,
                select2: {
                    placeholder: __('Choose field...')
                }
            });
        },

        editRow: function(data) {
            this.editViewId = data.view_id;
            this.fieldSelectorEl.select2(
                'val',
                this.options.workflow.getFieldIdByPropertyPath(data.property_path)
            );
            this.labelEl.val(data.isSystemLabel ? '' : data.label);
            this.requiredEl.get(0).checked = data.required;
            this.submitBtn.html('<i class="icon-edit"></i> ' + __('Update'));
            this.resetBtn.removeClass('hide');
        },

        render: function() {
            this.form = $(this.template(this.options.data)).filter('form');
            this.form.validate({
                'submitHandler': _.bind(this.onAdd, this)
            });
            this.form.on('submit', function(e) {e.preventDefault();});
            this.initFieldChoice(this.form);

            this.submitBtn = this.form.find('[type=submit]');
            this.resetBtn = this.form.find('[type=reset]');
            this.fieldSelectorEl = this.form.find('[name=property_path]');
            this.labelEl = this.form.find('[name=label]');
            this.requiredEl = this.form.find('[name=required]');

            this.resetBtn.click(_.bind(this.resetForm, this));

            this.$el.append(this.form);

            return this;
        }
    });

    return AttributeFormOptionEditView;
});
