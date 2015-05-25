/* global define */
define(function (require) {
    'use strict';

    var TransitionRowView,
        _ = require('underscore'),
        $ = require('jquery'),
        BaseView = require('oroui/js/app/views/base/view');

    TransitionRowView = BaseView.extend({
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

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#transition-row-template').html();
            this.template = _.template(template);
        },

        triggerRemoveTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestRemoveTransition', this.model);
        },

        render: function() {
            var data = this.model.toJSON();
            var stepTo = this.options.workflow.getStepByName(data.step_to);
            data.stepToLabel = stepTo ? stepTo.get('label') : '';
            this.$el.html(
                this.template(data)
            );

            return this;
        }
    });

    return TransitionRowView;
});
