/* global define */
define(['underscore', 'backbone', 'oroentity/js/field-choice'],
function(_, Backbone) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/attribute/form-option-view/edit
     * @class   oro.WorkflowManagement.AttributeFormOptionEditView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        attributes: {
            'class': 'widget-content'
        },

        events: {
            'click .form-option-add-btn': 'onAdd'
        },

        options: {
            template: null,
            entity_select_el: null
        },

        initialize: function() {
            var template = this.options.template || $('#attribute-form-option-edit-template').html();
            this.template = _.template(template);
        },

        onAdd: function() {
            var formData = {
                'property_path': this.addBlock.find('[name="form_option_property_path"]').val(),
                'label': this.addBlock.find('[name="form_option_label"]').val(),
                'required': this.addBlock.find('[name="form_option_required"]').is(':checked')
            };

            this.trigger('formOptionAdd', formData);
        },

        initFieldChoice: function(container) {
            container.find('[name="form_option_property_path"]').fieldChoice({
                fieldsLoaderSelector: this.options.entity_select_el,
                select2: {
                    placeholder: "Choose field..."
                }
            });
        },

        render: function() {
            var data = {
                'label': '',
                'property_path': '',
                'isRequired': false
            };
            this.addBlock = $(this.template(data));
            this.initFieldChoice(this.addBlock);
            this.$el.append(this.addBlock);

            return this;
        }
    });
});
