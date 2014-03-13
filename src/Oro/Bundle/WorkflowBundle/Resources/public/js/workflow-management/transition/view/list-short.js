/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/transition/view/row-short'],
function(_, Backbone, TransitionsShortRowView) {
    'use strict';

    /**
     * @export  oroworkflow/js/workflow-management/transition/view/list-short
     * @class   oro.WorkflowManagement.TransitionsShortListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'ul',

        attributes: {
            'class': 'transitions-list-short'
        },

        options: {
            collection: null,
            workflow: null,
            stepFrom: null
        },

        initialize: function() {
            this.rowViews = [];

            this.listenTo(this.getCollection(), 'add', this.addItem);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);
        },

        addItem: function(item) {
            var rowView = new TransitionsShortRowView({
                model: item,
                workflow: this.options.workflow,
                stepFrom: this.options.stepFrom
            });
            this.rowViews.push(rowView);
            this.$el.append(rowView.render().$el);
        },

        addAllItems: function(items) {
            _.each(items, this.addItem, this);
        },

        getCollection: function() {
            return this.options.collection;
        },

        remove: function() {
            this.resetView();
            Backbone.View.prototype.remove.call(this);
        },

        resetView: function() {
            _.each(this.rowViews, function (rowView) {
                rowView.remove();
            });
        },

        render: function() {
            this.addAllItems(this.getCollection().models);

            return this;
        }
    });
});
