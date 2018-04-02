define(function(require) {
    'use strict';

    var TransitionDialogWidget;
    var DialogWidget = require('oro/dialog-widget');
    var mediator = require('oroui/js/mediator');
    var TransitionEventHandlers = require('oroworkflow/js/transition-event-handlers');
    var $ = require('jquery');
    var _ = require('underscore');

    TransitionDialogWidget = DialogWidget.extend({
        listen: {
            transitionSuccess: 'onTransitionSuccess',
            transitionFailure: 'onTransitionFailure'
        },

        /**
         * @inheritDoc
         */
        constructor: function TransitionDialogWidget() {
            TransitionDialogWidget.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} response
         */
        onTransitionSuccess: function(response) {
            var element = $('<div>');
            this.hide();
            TransitionEventHandlers.getOnSuccess(element)(response);
        },

        /**
         * @param {XMLHttpRequest} jqxhr
         */
        onTransitionFailure: function(jqxhr) {
            var element = $('<div>');
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
                var element = $('<div>');
                this.hide();
                mediator.execute('showLoading');
                TransitionEventHandlers.getOnSuccess(element)(content);
            }

            return TransitionDialogWidget.__super__._onContentLoad.apply(this, arguments);
        }
    });

    return TransitionDialogWidget;
});
