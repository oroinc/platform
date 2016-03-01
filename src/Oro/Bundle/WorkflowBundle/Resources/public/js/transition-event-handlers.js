/*jslint nomen:true*/
/*global define*/
define([
    'oroui/js/messenger',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function(messenger, __, mediator) {
    'use strict';

    /**
     * TransitionEventHandlers
     *
     * @export  oroworkflow/js/transition-event-handlers
     * @class   oro.WorkflowTransitionEventHandlers
     */
    return {
        getOnSuccess: function(element) {
            return function(response) {
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
            };
        },
        getOnFailure: function(element) {
            return function(jqxhr, textStatus, error) {
                mediator.execute('hideLoading');
                element.one('transitions_failure', function() {
                    messenger.notificationFlashMessage('error', __('Could not perform transition'));
                });
                element.trigger('transitions_failure', [jqxhr, textStatus, error]);
            };
        }
    };
});
