/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/step/view/row'],
function(_, Backbone, StepsCollection, StepRowView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/step/view/list
     * @class   oro.WorkflowManagement.StepsListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'tr',
        workflow: null,
        options: {
            template:
                '<td><%= name %></td>' +
                '<td><%= transitions %></td>' +
                '<td>' +
                    '<a href="#" class="edit-step"><i class="icon-edit"/> Edit</a> ' +
                    '<a href="#" class="delete-step"><i class="icon-remove"/> Delete</a> ' +
                    '<a href="#" class="delete-step"><i class="icon-plus-sign"/> Add transition</a> ' +
                    '</td>'
        },

        initialize: function() {
            this.template = _.template(this.options.template);
            this.listenTo(this.model, 'destroy', this.remove);
        },

        render: function() {
            var data = this.options.model.toJSON();
            data.transitions = new TransitionsShortListView(
                this.options.model.get('allowedTransitions'),
                this.options.workflow.get('transitions')
            );

            this.$el.append(this.template(data));

            return this;
        }
    });
});
