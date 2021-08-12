define(function(require) {
    'use strict';

    const DialogWidget = require('oro/dialog-widget');
    const mediator = require('oroui/js/mediator');
    const TransitionEventHandlers = require('oroworkflow/js/transition-event-handlers');
    const $ = require('jquery');
    const _ = require('underscore');

    const TransitionDialogWidget = DialogWidget.extend({
        listen: {
            transitionSuccess: 'onTransitionSuccess',
            transitionFailure: 'onTransitionFailure'
        },

        /**
         * @inheritdoc
         */
        constructor: function TransitionDialogWidget(options) {
            TransitionDialogWidget.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} response
         */
        onTransitionSuccess: function(response) {
            const element = $('<div>');
            this.hide();
            TransitionEventHandlers.getOnSuccess(element)(response);
        },

        /**
         * @param {XMLHttpRequest} jqxhr
         */
        onTransitionFailure: function(jqxhr) {
            const element = $('<div>');
            this.hide();
            TransitionEventHandlers.getOnFailure(element)(jqxhr);
        },

        /**
         * @param {Object} content
         * @returns {Object}
         * @private
         */
        _onContentLoad: function(content) {
            if (_.has(content, 'workflowItem')) {
                const element = $('<div>');
                this.hide();
                mediator.execute('showLoading');
                TransitionEventHandlers.getOnSuccess(element)(content);
            }

            return TransitionDialogWidget.__super__._onContentLoad.call(this, content);
        }
    });

    return TransitionDialogWidget;
});
