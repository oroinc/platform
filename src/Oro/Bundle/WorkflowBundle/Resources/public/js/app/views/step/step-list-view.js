define(function(require) {
    'use strict';

    var StepsListView;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var StepRowView = require('./step-row-view');

    StepsListView = BaseView.extend({
        options: {
            listElBodyEl: 'tbody',
            template: null,
            workflow: null
        },

        /**
         * @inheritDoc
         */
        constructor: function StepsListView() {
            StepsListView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#step-list-template').html();
            this.template = _.template(template);
            this.rowViews = [];

            this.$listEl = $(this.template());
            this.$listElBody = this.$listEl.find(this.options.listElBodyEl);
            this.$el.html(this.$listEl);

            this.listenTo(this.getCollection(), 'change', this.render);
            this.listenTo(this.getCollection(), 'add', this.render);
            this.listenTo(this.getCollection(), 'reset', this.addAllItems);

            this.listenTo(this.getTransitionCollection(), 'change', this.updateDataFields);
            this.listenTo(this.getTransitionCollection(), 'add', this.updateDataFields);
            this.listenTo(this.getTransitionCollection(), 'reset', this.updateDataFields);
        },

        addItem: function(item) {
            var rowView = new StepRowView({
                model: item,
                workflow: this.options.workflow
            });
            this.rowViews.push(rowView);
            this.$listElBody.append(rowView.render().$el);
        },

        addAllItems: function(collection) {
            this.resetView();
            collection.each(_.bind(this.addItem, this));
        },

        getCollection: function() {
            return this.options.workflow.get('steps');
        },

        getTransitionCollection: function() {
            return this.options.workflow.get('transitions');
        },

        remove: function() {
            this.resetView();
            StepsListView.__super__.remove.call(this);
        },

        resetView: function() {
            _.each(this.rowViews, function(rowView) {
                rowView.remove();
            });
            this.rowViews = [];
        },

        updateDataFields: function() {
            this.$listEl.find('[name="oro_workflow_definition_form[steps]"]').val(JSON.stringify(this.getCollection()));
            this.$listEl.find('[name="oro_workflow_definition_form[transitions]"]').val(
                JSON.stringify(this.getTransitionCollection()));
        },

        render: function() {
            this.getCollection().sort();
            this.addAllItems(this.getCollection());
            this.updateDataFields();
            return this;
        }
    });

    return StepsListView;
});
