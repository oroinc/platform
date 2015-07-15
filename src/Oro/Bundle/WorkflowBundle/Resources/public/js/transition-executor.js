define([
    'jquery',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, messenger, __, mediator) {
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
            .done(function(response) {
                mediator.execute('hideLoading');
                function doRedirect(redirectUrl) {
                    mediator.execute('redirectTo', {url: redirectUrl});
                }
                function doReload() {
                    mediator.execute('refreshPage');
                }

                /** Handle redirectUrl result parameter for RedirectAction */
                element.one('transitions_success', function(e, response) {
                    if (
                        response.workflowItem &&
                            response.workflowItem.result &&
                            response.workflowItem.result.redirectUrl
                    ) {
                        e.stopImmediatePropagation();
                        doRedirect(response.workflowItem.result.redirectUrl);
                    }
                });
                /** By default reload page */
                element.one('transitions_success', doReload);
                element.trigger('transitions_success', [response]);
            })
            .fail(function(jqxhr, textStatus, error) {
                mediator.execute('hideLoading');
                element.one('transitions_failure', function() {
                    messenger.notificationFlashMessage('error', __('Could not perform transition'));
                });
                element.trigger('transitions_failure', [jqxhr, textStatus, error]);
            });
    };
});
