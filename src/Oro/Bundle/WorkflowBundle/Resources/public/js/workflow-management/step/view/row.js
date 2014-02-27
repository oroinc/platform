/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/transition/view/list-short'],
function(_, Backbone, TransitionsShortListView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/step/view/row
     * @class   oro.WorkflowManagement.StepRowView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'tr',
        events: {
            'click .delete-step': 'remove'
        },

        options: {
            workflow: null,
            template: null
        },

        initialize: function() {
            var template = this.options.template || $('#step-row-template').html();
            this.template = _.template(template);
            this.listenTo(this.model, 'destroy', this.remove);
        },

        render: function() {
            var transitionsList = new TransitionsShortListView({
                'collection': this.model.getAllowedTransitions(this.options.workflow),
                'workflow': this.options.workflow
            });
            var rowHtml = $(this.template(this.model.toJSON()));
            var transitionsListEl = transitionsList.render().$el;
            rowHtml.filter('.step-transitions').append(transitionsListEl);
            this.$el.append(rowHtml);

            return this;
        }
    });
});
