/* global define */
define(['underscore', 'backbone', 'oroworkflow/js/workflow-management/attribute/form-option-view/row'],
function(_, Backbone, AttributeFormOptionRowView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oroworkflow/js/workflow-management/attribute/form-option-view/list
     * @class   oro.WorkflowManagement.AttributeFormOptionListView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            collection: []
        },

        initialize: function() {
            var template = this.options.template || $('#attribute-form-option-list-template').html();
            this.template = _.template(template);
            this.rowViews = [];

            this.$listEl = $(this.template());
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);
            this.$el.html(this.$listEl);
        },

        addAllItems: function(items) {
            _.each(items, this.addItem, this);
        },

        addItem: function(data) {
            var rowView = new AttributeFormOptionRowView({
                data: data,
                workflow: this.options.workflow
            });
            this.rowViews.push(rowView);
            this.$listElBody.append(rowView.render().$el);
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
            this.resetView();
            this.addAllItems(this.getCollection());

            return this;
        }
    });
});
