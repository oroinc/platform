define([
    'jquery',
    'underscore',
    'oroui/js/modal',
    'oroui/js/tools',
    'backbone',
    'oroworkflow/js/transition-executor'
], function($, _, Modal, tools, Backbone, performTransition) {
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
            var additionalOptions = element.data('data-dialog-options');
            if (additionalOptions) {
                if (!_.isUndefined(additionalOptions)) {
                    additionalOptions.dialogOptions = _.extend(
                        dialogOptions.dialogOptions,
                        additionalOptions.dialogOptions
                    );
                }
                dialogOptions = _.extend(dialogOptions, additionalOptions);
            }

            tools.loadModules('oroworkflow/transition-dialog-widget', function(Widget) {
                var _widget = new Widget(dialogOptions);
                Backbone.listenTo(_widget, 'widgetRemove', _.bind(function() {
                    resetInProgress();
                }, this));

                _widget.render();
            });
        };

        var showConfirmationModal = function() {
            var message = element.data('message');
            var modalOptions = {
                title: element.data('transition-label'),
                content: message
            };
            if (element.data('transition-confirmation-options')) {
                modalOptions = $.extend({}, modalOptions, element.data('transition-confirmation-options'));
                modalOptions.template = _.template(element.data('transition-confirmation-options').template);
            }
            var confirm = new Modal(modalOptions);

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
