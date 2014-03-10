/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/transition/view/row'],
function(_, Backbone, TransitionRowView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/transition/view/list
     * @class   oro.WorkflowManagement.TransitionsListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            workflow: null,
            collection: null,
            stepFrom: null
        },

        initialize: function() {
            var template = this.options.template || $('#transition-list-template').html();
            this.template = _.template(template);
            this.rowViews = [];

            this.$listEl = $(this.template());
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);
            this.$el.html(this.$listEl);

            this.listenTo(this.getCollection(), 'change', this.render);
            this.listenTo(this.getCollection(), 'add', this.render);
            this.listenTo(this.getCollection(), 'remove', this.render);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);
        },

        addItem: function(item) {
            var rowView = new TransitionRowView({
                model: item,
                workflow: this.options.workflow,
                stepFrom: this.options.stepFrom
            });
            this.rowViews.push(rowView);
            this.$listElBody.append(rowView.render().$el);
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
            this.rowViews = [];
        },

        render: function() {
            if (this.getCollection().models.length) {
                this.$el.show();
                this.resetView();
                this.addAllItems(this.getCollection().models);
            } else {
                this.$el.hide();
            }

            return this;
        }
    });
});
