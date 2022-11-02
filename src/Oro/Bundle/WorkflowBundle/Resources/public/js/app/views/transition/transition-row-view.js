define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const TransitionRowView = BaseView.extend({
        tagName: 'tr',

        events: {
            'click .delete-transition': 'triggerRemoveTransition'
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
        constructor: function TransitionRowView(options) {
            TransitionRowView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const template = this.options.template || $('#transition-row-template').html();
            this.template = _.template(template);
        },

        triggerRemoveTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestRemoveTransition', this.model);
        },

        render: function() {
            const data = this.model.toJSON();
            const stepTo = this.options.workflow.getStepByName(data.step_to);
            data.stepToLabel = stepTo ? stepTo.get('label') : '';
            this.$el.html(
                this.template(data)
            );

            return this;
        }
    });

    return TransitionRowView;
});
