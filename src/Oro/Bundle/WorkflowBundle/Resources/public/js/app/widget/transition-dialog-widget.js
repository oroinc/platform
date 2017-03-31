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
            'transitionSuccess': 'onTransitionSuccess',
            'transitionFailure': 'onTransitionFailure'
        },
        onTransitionSuccess: function(response) {
            var element = $('<div>');
            this.hide();
            TransitionEventHandlers.getOnSuccess(element)(response);
        },
        onTransitionFailure: function(jqxhr) {
            var element = $('<div>');
            this.hide();
            TransitionEventHandlers.getOnFailure(element)(jqxhr);
        },
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
