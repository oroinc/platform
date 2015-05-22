/* global define */
define(function (require) {
    'use strict';

    var TransitionsShortRowView,
        _ = require('underscore'),
        $ = require('jquery'),
        BaseView = require('oroui/js/app/views/base/view');

    TransitionsShortRowView = BaseView.extend({
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

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            var template = this.options.template || $('#transition-row-short-template').html();
            this.template = _.template(template);
        },

        deleteTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestRemoveTransition', this.model);
        },

        triggerEditTransition: function (e) {
            e.preventDefault();
            this.options.workflow.trigger('requestEditTransition', this.model, this.options.stepFrom);
        },

        triggerCloneTransition: function(e) {
            e.preventDefault();
            this.options.workflow.trigger('requestCloneTransition', this.model, this.options.stepFrom);
        },

        render: function() {
            var data = this.model.toJSON();
            var stepTo = this.options.workflow.getStepByName(data.step_to);
            data.stepToLabel = stepTo ? stepTo.get('label') : '';

            this.$el.html(this.template(data));

            return this;
        }
    });

    return TransitionsShortRowView;
});
