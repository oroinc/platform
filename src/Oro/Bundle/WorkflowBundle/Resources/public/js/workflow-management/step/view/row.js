/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/transition/view/list-short'],
function(_, Backbone, TransitionsShortListView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/step/view/list
     * @class   oro.WorkflowManagement.StepsListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'tr',
        events: {
            'click .delete-step': 'remove'
        },

        options: {
            workflow: null,
            template:
                '<td class="step-name">' +
                    '<% if (_isStart) { %><%= label %><% } else { %><a href="#"><%= label %></a><% } %>' +
                    '<% if (isFinal) { %>&nbsp;<span class="label">Final</span><% } %>' +
                '</td>' +
                '<td class="step-transitions"></td>' +
                '<td class="step-actions"><div class="pull-right">' +
                    '<% if (!_isStart) { %>' +
                    '<a href="#" class="edit-step" title="Edit step"><i class="icon-edit"/></a> ' +
                    '<a href="#" class="delete-step" title="Delete step"><i class="icon-remove"/></a> ' +
                    '<% } %>' +
                    '<a href="#" class="add-step-transition" title="Add transition"><i class="icon-plus-sign"/></a> ' +
                '</div></td>'
        },

        initialize: function() {
            this.template = _.template(this.options.template);
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
