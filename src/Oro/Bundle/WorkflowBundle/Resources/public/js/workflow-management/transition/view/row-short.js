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
            template: null
        },

        initialize: function() {
            var template = this.options.template || $('#transition-row-short-template').html();
            this.template = _.template(template);
            this.listenTo(this.options.model, 'destroy', this.remove);
        },

        render: function() {
            var data = this.model.toJSON();
            var stepTo = this.options.workflow.getStepByName(data.step_to);
            data.stepToLabel = stepTo ? stepTo.get('label') : '';

            this.$el.html(this.template(data));

            return this;
        }
    });
});
