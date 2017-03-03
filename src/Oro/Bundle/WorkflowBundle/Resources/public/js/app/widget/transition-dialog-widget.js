define(function(require) {
    'use strict';

    var TransitionDialogWidget;
    var DialogWidget = require('oro/dialog-widget');
    var TransitionEventHandlers = require('oroworkflow/js/transition-event-handlers');
    var $ = require('jquery');

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
        }
    });

    return TransitionDialogWidget;
});
