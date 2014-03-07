/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/attribute/form-option-view/row
     * @class   oro.WorkflowManagement.AttributeFormOptionRowView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'tr',

        events: {
            'click .delete-form-option': 'triggerRemove',
            'click .edit-form-option': 'triggerEdit'
        },

        options: {
            workflow: null,
            template: null,
            data: {
                'label': null,
                'property_path': null,
                'required': false
            }
        },

        initialize: function() {
            var template = this.options.template || $('#attribute-form-option-row-template').html();
            this.template = _.template(template);
            this.options.data.view_id = this.cid;
        },

        triggerEdit: function(e) {
            e.preventDefault();
            this.trigger('editFormOption', this.options.data);
        },

        triggerRemove: function(e) {
            e.preventDefault();
            this.trigger('removeFormOption', this.options.data);
            this.remove();
        },

        render: function() {
            var rowHtml = $(this.template(this.options.data));
            this.$el.html(rowHtml);

            return this;
        }
    });
});
