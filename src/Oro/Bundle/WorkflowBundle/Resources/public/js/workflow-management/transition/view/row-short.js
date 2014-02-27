/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    /**
     * @export  oro/workflow-management/transition/view/row-short
     * @class   oro.WorkflowManagement.TransitionsShortRowView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'li',

        options: {
            workflow: null,
            template:
                '<a href="#"><%= label %></a> <i class="icon-long-arrow-right"/> <span><%= stepToLabel %></span>'
        },

        initialize: function() {
            this.template = _.template(this.options.template);
            this.listenTo(this.options.model, 'destroy', this.remove);
        },

        render: function() {
            var data = this.model.toJSON();
            var stepTo = this.options.workflow.getStepByName(data.stepTo);
            data.stepToLabel = stepTo ? stepTo.get('label') : '';

            this.$el.html(this.template(data));

            return this;
        }
    });
});
