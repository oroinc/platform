/* global define */
define(['underscore', 'backbone', 'oro/workflow-management/transition/view/row-short'],
function(_, Backbone, TransitionsShortRowView) {
    'use strict';

    /**
     * @export  oro/workflow-management/transition/view/list-short
     * @class   oro.WorkflowManagement.TransitionsShortListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        tagName: 'ul',

        options: {
            collection: null,
            workflow: null
        },

        initialize: function() {
            this.listenTo(this.getCollection(), 'add', this.addItem);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);
        },

        addItem: function(item) {
            var rowView = new TransitionsShortRowView({
                model: item,
                workflow: this.options.workflow
            });
            this.$el.append(rowView.render().$el);
        },

        addAllItems: function(items) {
            _.each(items, this.addItem, this);
        },

        getCollection: function() {
            return this.options.collection;
        },

        render: function() {
            this.addAllItems(this.getCollection().models);

            return this;
        }
    });
});
