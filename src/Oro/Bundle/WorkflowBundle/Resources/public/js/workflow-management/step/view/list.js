/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/step/collection', 'oro/workflow-management/step/view/row'],
function(_, Backbone, StepsCollection, StepRowView) {
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
            template: '<table>' +
                '<thead>' +
                '<th>Step name</th>' +
                '<th>Transitions</th>' +
                '<th>Actions</th>' +
                '</thead><tbody></tbody>' +
                '</table>',
            collection: null
        },

        initialize: function() {
            this.options.collection = this.options.collection || new StepsCollection();
            this.template = _.template(this.options.template);
            this.$listEl = this.template();
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);

            this.listenTo(this.getCollection(), 'add', this.addItem);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);

            this.reorderSteps();
        },

        reorderSteps: function() {
            _.sortBy(this.getCollection(), function(item) {
                return parseInt(item.get('order'));
            });
        },

        addItem: function(item) {
            var stepRowView = new StepRowView({model: item});
            this.$listElBody.append(stepRowView.render().$el);
        },

        addAllItems: function(items) {
            _.each(items, _.bind(
                function(item) {
                    this.addItem(item);
                },
                this)
            );
        },

        getCollection: function() {
            return this.options.collection;
        },

        render: function() {
            this.$el.html(this.$listEl);

            return this;
        }
    });
});
