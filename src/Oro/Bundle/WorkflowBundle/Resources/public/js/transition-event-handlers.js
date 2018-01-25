define([
    'underscore',
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function(_, messenger, __, mediator) {
    'use strict';

    /**
     * TransitionEventHandlers
     *
     * @export  oroworkflow/js/transition-event-handlers
     * @class   oro.WorkflowTransitionEventHandlers
     */
    return {
        getOnStart: function(element, pageRefresh) {
            return function() {
                if (pageRefresh) {
                    mediator.execute('showLoading');
                }

                element.trigger('transitions_start');
            };
        },
        getOnSuccess: function(element, pageRefresh) {
            return function(response) {
                pageRefresh = _.isUndefined(pageRefresh) ? true : pageRefresh;

                function doRedirect(redirectUrl) {
                    mediator.execute('redirectTo', {url: redirectUrl}, {redirect: true});
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
                if (pageRefresh) {
                    element.one('transitions_success', doReload);
                }

                element.trigger('transitions_success', [response]);
            };
        },
        getOnFailure: function(element, pageRefresh) {
            return function(jqxhr, textStatus, error) {
                pageRefresh = _.isUndefined(pageRefresh) ? true : pageRefresh;

                if (pageRefresh) {
                    mediator.execute('hideLoading');
                }

                element.one('transitions_failure', function() {
                    var message = __('Could not perform transition');
                    if (jqxhr.message !== undefined) {
                        message += ': ' + jqxhr.message;
                    }
                    messenger.notificationFlashMessage('error', message);
                });
                element.trigger('transitions_failure', [jqxhr, textStatus, error]);
                mediator.trigger('workflow:transitions_failure', element, jqxhr, textStatus, error);
            };
        }
    };
});
