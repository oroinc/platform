define(['jquery', 'oro/messenger', 'oro/translator', 'oro/navigation'],
function($, messenger, __, Navigation) {
    'use strict';

    var navigation = Navigation.getInstance();

    /**
     * Transition executor
     *
     * @export  oro/workflow-transition-executor
     * @class   oro.WorkflowTransitionExecutor
     */
    return function(element, data) {
        $.getJSON(element.data('transition-url'), data ? {'data': data} : null)
            .done(function(response) {
                var doRedirect = function(redirectUrl) {
                    if (navigation) {
                        navigation.setLocation(redirectUrl);
                    } else {
                        window.location.href = redirectUrl;
                    }
                };
                var doReload = function() {
                    if (navigation) {
                        navigation.loadPage();
                    } else {
                        window.location.reload();
                    }
                };

                /** Handle redirectUrl result parameter for RedirectAction */
                element.one('transitions_success', function(e, response) {
                    if (response.workflowItem
                        && response.workflowItem.result
                        && response.workflowItem.result.redirectUrl
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
                element.one('transitions_failure', function() {
                    messenger.notificationFlashMessage('error', __('Could not perform transition'));
                });
                element.trigger('transitions_failure', [jqxhr, textStatus, error]);
            });
    };
});
