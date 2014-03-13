/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/helper', 'orotranslation/js/translator', 'oroentity/js/field-choice'],
function(_, Backbone, Helper, __) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/attribute/form-option-view/edit
     * @class   oro.WorkflowManagement.AttributeFormOptionEditView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
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

        initialize: function() {
            var template = this.options.template || $('#attribute-form-option-edit-template').html();
            this.template = _.template(template);

            this.entity_field_template = this.options.entity_field_template || $('#entity-column-chain-template').html();

            this.editViewId = null;
        },

        getFieldSelector: function() {
            return this.entityFieldSelectEl;
        },

        onAdd: function() {
            var formData = Helper.getFormData(this.form);

            formData.property_path = this.getPropertyPath(formData.property_path);
            formData.required = formData.hasOwnProperty('required');
            formData.view_id = this.editViewId;

            this.resetForm();
            this.trigger('formOptionAdd', formData);
        },

        getPropertyPath: function(propertyPath) {
            var path = [this.options.workflow.get('entity_attribute')];
            var parts = propertyPath.split('::');
            _.each(parts, function(part) {
                var propertyData = part.split('+');
                path.push(propertyData[0]);
            });
            return path.join('.');
        },

        resetForm: function() {
            this.editViewId = null;
            this.entityFieldSelectEl.select2('val', '');
            this.form.get(0).reset();
            this.submitBtn.html('<i class="icon-plus"></i> ' + __('Add'));
        },

        initFieldChoice: function(container) {
            var workflow = this.options.workflow;

            this.entityFieldSelectEl = container.find('[name="property_path"]');
            this.entityFieldSelectEl.fieldChoice({
                fieldsLoaderSelector: this.options.entity_select_el,
                select2: {
                    placeholder: __("Choose field..."),
                    formatSelectionTemplate: this.entity_field_template
                },
                util: {
                    findEntity:  function (entityName) {
                        return _.findWhere(workflow.getSystemEntities(), {name: entityName});
                    }
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
        },

        render: function() {
            this.form = $(this.template(this.options.data)).filter('form');
            this.form.validate({
                'submitHandler': _.bind(this.onAdd, this)
            });
            this.form.on('submit', function(e) {e.preventDefault();});
            this.initFieldChoice(this.form);

            this.submitBtn = this.form.find('[type=submit]');
            this.fieldSelectorEl = this.form.find('[name=property_path]');
            this.labelEl = this.form.find('[name=label]');
            this.requiredEl = this.form.find('[name=required]');

            this.$el.append(this.form);

            return this;
        }
    });
});
