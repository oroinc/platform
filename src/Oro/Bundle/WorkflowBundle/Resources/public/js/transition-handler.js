define([
    'jquery',
    'underscore',
    'oroui/js/modal',
    'oroworkflow/js/transition-executor',
    'oroworkflow/js/transition-event-handlers',
], function($, _, Modal, performTransition, TransitionEventHandlers) {
    'use strict';

    /**
     * Transition button click handler
     *
     * @export  oroworkflow/js/transition-handler
     * @class   oroworkflow.WorkflowTransitionHandler
     */
    return function(pageRefresh) {
        var element = $(this);
        pageRefresh = _.isUndefined(pageRefresh) ? true : pageRefresh;

        var resetInProgress = function() {
            element.data('_in-progress', false);
        };

        var showDialog = function() {
            var dialogOptions = {
                title: element.data('transition-label') || element.html(),
                url: element.data('dialog-url'),
                stateEnabled: false,
                incrementalPosition: false,
                loadingMaskEnabled: true,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    width: 475,
                    autoResize: true
                }
            };
            var additionalOptions = element.data('dialog-options');
            if (additionalOptions) {
                if (!_.isUndefined(additionalOptions)) {
                    additionalOptions.dialogOptions = _.extend(
                        dialogOptions.dialogOptions,
                        additionalOptions.dialogOptions
                    );
                }
                dialogOptions = _.extend(dialogOptions, additionalOptions);
            }

            require(['oro/dialog-widget'],
                function(DialogWidget) {
                    var transitionFormWidget = new DialogWidget(dialogOptions);
                    transitionFormWidget.on('widgetRemove', function() {
                        resetInProgress();
                    });

                    transitionFormWidget.on('formSave', function(data) {
                        transitionFormWidget.remove();
                        performTransition(element, data, pageRefresh);
                    });

                    transitionFormWidget.on('transitionSuccess', function(response) {
                        transitionFormWidget.remove();
                        TransitionEventHandlers.getOnSuccess(element, pageRefresh)(response);
                    });

                    transitionFormWidget.on('transitionFailure', function(jqxhr) {
                        transitionFormWidget.remove();
                        TransitionEventHandlers.getOnFailure(element, pageRefresh)(jqxhr);
                    });

                    transitionFormWidget.render();
                }
            );
        };

        var showConfirmationModal = function() {
            var message = element.data('message');
            var confirm = new Modal({
                title: element.data('transition-label'),
                content: message
            });

            confirm.on('ok', function() {
                performTransition(element, null, pageRefresh);
            });
            confirm.on('cancel', function() {
                resetInProgress();
            });
            confirm.open();
        };

        if (element.data('_in-progress')) {
            return;
        }

        element.data('_in-progress', true);
        element.one('transitions_success', resetInProgress);
        element.one('transitions_failure', resetInProgress);

        if (element.data('dialog-url')) {
            showDialog();
            return;
        }

        if (element.data('message')) {
            showConfirmationModal();
            return;
        }

        performTransition(element, null, pageRefresh);
    };

});
