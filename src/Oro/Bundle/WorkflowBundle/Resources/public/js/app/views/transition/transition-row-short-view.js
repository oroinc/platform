define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const TransitionsShortRowView = BaseView.extend({
        tagName: 'li',

        events: {
            'click .edit-transition': 'triggerEditTransition',
            'click .clone-transition': 'triggerCloneTransition',
            'click .delete-transition': 'deleteTransition'
        },

        options: {
            workflow: null,
            template: null,
            stepFrom: null
        },

        listen: {
            'destroy model': 'remove',
            'change model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function TransitionsShortRowView(options) {
            TransitionsShortRowView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const template = this.options.template || $('#transition-row-short-template').html();
            this.template = _.template(template);
        },

        deleteTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestRemoveTransition', this.model);
        },

        triggerEditTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestEditTransition', this.model, this.options.stepFrom);
        },

        triggerCloneTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestCloneTransition', this.model, this.options.stepFrom);
        },

        render: function() {
            const data = this.model.toJSON();
            const stepTo = this.options.workflow.getStepByName(data.step_to);
            data.stepToLabel = stepTo ? stepTo.get('label') : '';

            this.$el.html(this.template(data));

            return this;
        }
    });

    return TransitionsShortRowView;
});
