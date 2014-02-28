/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/step/view/row'],
function(_, Backbone, StepRowView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/workflow-management/step/view/list
     * @class   oro.WorkflowManagement.StepsListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            workflow: null
        },

        initialize: function() {
            var template = this.options.template || $('#step-list-template').html();
            this.template = _.template(template);
            this.$listEl = $(this.template());
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);

            this.listenTo(this.getCollection(), 'change', this.render);
            this.listenTo(this.getCollection(), 'add', this.addItem);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);
        },

        addItem: function(item) {
            var rowView = new StepRowView({
                model: item,
                workflow: this.options.workflow
            });
            this.$listElBody.append(rowView.render().$el);
        },

        addAllItems: function(items) {
            _.each(items, _.bind(this.addItem, this));
        },

        getCollection: function() {
            return this.options.workflow.get('steps');
        },

        render: function() {
            this.getCollection().sort();
            this.$listElBody.empty();
            this.addAllItems(this.getCollection().models);
            this.$el.html(this.$listEl);

            return this;
        }
    });
});
