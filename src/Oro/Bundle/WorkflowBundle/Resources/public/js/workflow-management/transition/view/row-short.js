/* global define */
define(['underscore', 'backbone'],
function(_, Backbone) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/transition/view/row-short
     * @class   oro.WorkflowManagement.TransitionsShortRowView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'li',

        events: {
            'click .edit-transition': 'triggerEditTransition',
            'click .delete-transition': 'deleteTransition'
        },

        options: {
            workflow: null,
            template: null
        },

        initialize: function() {
            var template = this.options.template || $('#transition-row-short-template').html();
            this.template = _.template(template);

            this.listenTo(this.options.model, 'change', this.render);
            this.listenTo(this.options.model, 'destroy', this.remove);
        },

        deleteTransition: function(e) {
            e.preventDefault();
            this.model.destroy();
        },

        triggerEditTransition: function (e) {
            e.preventDefault();
            this.model.trigger('requestEdit', this.model);
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
