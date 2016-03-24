define([
    'jquery',
    'oroui/js/mediator',
    'oroworkflow/js/transition-event-handlers',
], function($, mediator, TransitionEventHandlers) {
    'use strict';

    /**
     * Transition executor
     *
     * @export  oroworkflow/js/transition-executor
     * @class   oro.WorkflowTransitionExecutor
     */
    return function(element, data) {
        mediator.execute('showLoading');
        $.getJSON(element.data('transition-url'), data ? {'data': data} : null)
            .done(TransitionEventHandlers.getOnSuccess(element))
            .fail(TransitionEventHandlers.getOnFailure(element));
    };
});
