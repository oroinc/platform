import _ from 'underscore';
import messenger from 'oroui/js/messenger';
import __ from 'orotranslation/js/translator';
import mediator from 'oroui/js/mediator';

/**
 * TransitionEventHandlers
 *
 * @export  oroworkflow/js/transition-event-handlers
 * @class   oro.WorkflowTransitionEventHandlers
 */
export default {
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
                mediator.execute('redirectTo', {url: redirectUrl}, {redirect: true})
                    // in case redirect action was canceled -- remove loading mask
                    .fail(() => mediator.execute('hideLoading'));
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
                } else if (pageRefresh) {
                    /** By default, reload page */
                    doReload();
                }
            });

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
                if (jqxhr.status === 403 && !_.isUndefined(jqxhr.responseJSON.message)) {
                    return;
                }

                let message = __('Could not perform transition');
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
