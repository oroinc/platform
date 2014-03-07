/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/helper', 'oroentity/js/field-choice'],
function(_, Backbone, Helper) {
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
        },

        getFieldSelector: function() {
            return this.entityFieldSelectEl;
        },

        onAdd: function() {
            var formData = Helper.getFormData(this.form);

            formData.property_path = this.getPropertyPath(formData.property_path);
            formData.required = formData.hasOwnProperty('required');

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
            this.entityFieldSelectEl.select2('val', '');
            this.form.get(0).reset();
        },

        initFieldChoice: function(container) {
            var workflow = this.options.workflow;

            this.entityFieldSelectEl = container.find('[name="property_path"]');
            this.entityFieldSelectEl.fieldChoice({
                fieldsLoaderSelector: this.options.entity_select_el,
                select2: {
                    placeholder: "Choose field...",
                    formatSelectionTemplate: this.entity_field_template
                },
                util: {
                    findEntity:  function (entityName) {
                        return _.findWhere(workflow.getSystemEntities(), {name: entityName});
                    }
                }
            });
        },

        render: function(data) {
            data = data || this.options.data;
            this.form = $(this.template(data)).filter('form');
            this.form.validate({
                'submitHandler': _.bind(this.onAdd, this)
            });
            this.initFieldChoice(this.form);
            this.$el.append(this.form);

            return this;
        }
    });
});
