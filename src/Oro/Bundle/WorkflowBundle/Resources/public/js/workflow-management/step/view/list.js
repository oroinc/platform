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
        events: {
            'click .edit-step': 'editStep',
            'click .delete-step': 'removeStep',
            'click .add-step-transition': 'addStepTransition'
        },

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

        editStep: function(e) {
            e.preventDefault();
            var stepName = $(e.target).closest('.edit-step').data('step-name');

            this.trigger('stepEdit', stepName);
        },

        removeStep: function(e) {
            e.preventDefault();
            var stepName = $(e.target).closest('.edit-step').data('step-name');

            this.trigger('stepRemove', stepName);
        },

        addStepTransition: function(e) {
            e.preventDefault();
            var stepName = $(e.target).closest('.edit-step').data('step-name');

            this.trigger('stepAddTransition', stepName);
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
