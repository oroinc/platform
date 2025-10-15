import $ from 'jquery';
import TransitionEventHandlers from 'oroworkflow/js/transition-event-handlers';

/**
 * Transition executor
 *
 * @export  oroworkflow/js/transition-executor
 * @class   oro.WorkflowTransitionExecutor
 */
export default function(element, data, pageRefresh) {
    TransitionEventHandlers.getOnStart(element, pageRefresh)();

    $.post(element.data('transition-url'), data ? {data: data} : null, null, 'json')
        .done(TransitionEventHandlers.getOnSuccess(element, pageRefresh))
        .fail(TransitionEventHandlers.getOnFailure(element, pageRefresh));
};
