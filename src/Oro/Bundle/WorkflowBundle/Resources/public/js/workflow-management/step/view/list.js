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
            template: '<table class="grid table-hover table table-bordered table-condensed">' +
                '<thead>' +
                '<th>Step name</th>' +
                '<th>Transitions</th>' +
                '<th>Actions</th>' +
                '</thead><tbody></tbody>' +
                '</table>',
            collection: null,
            workflow: null
        },

        initialize: function() {
            this.template = _.template(this.options.template);
            this.$listEl = $(this.template());
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);

            this.addAllItems(this.getCollection().models);

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
            return this.options.collection;
        },

        render: function() {
            this.$el.html(this.$listEl);

            return this;
        }
    });
});
