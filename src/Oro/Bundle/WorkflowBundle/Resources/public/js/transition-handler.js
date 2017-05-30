define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal',
    'oroui/js/tools',
    'backbone',
    'oroworkflow/js/transition-executor'
], function($, _, __, Modal, tools, Backbone, performTransition) {
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

            tools.loadModules('oroworkflow/transition-dialog-widget', function(Widget) {
                var _widget = new Widget(dialogOptions);
                Backbone.listenTo(_widget, 'widgetRemove', _.bind(function() {
                    resetInProgress();
                }, this));

                _widget.render();
            });
        };

        /**
         * @param {function} callback
         */
        var showConfirmationModal = function(callback) {
            var message = element.data('message');
            var modalOptions = {};
            if (typeof message === 'string') {
                modalOptions = {
                    content: message,
                    title: element.data('transition-label')
                };
            } else {
                modalOptions = message;
            }

            var confirmation = element.data('confirmation') || {};
            var placeholders = {};
            if (confirmation.message_parameters !== undefined) {
                placeholders = confirmation.message_parameters;
            }
            if (confirmation.title || '') {
                modalOptions.title = __(confirmation.title, $.extend({}, placeholders));
            }
            if (confirmation.message || '') {
                modalOptions.content = __(confirmation.message, $.extend({}, placeholders));
            }
            if (confirmation.okText || '') {
                modalOptions.okText = __(confirmation.okText, $.extend({}, placeholders));
            }
            if (confirmation.cancelText || '') {
                modalOptions.cancelText = __(confirmation.cancelText, $.extend({}, placeholders));
            }

            var confirm = new Modal(modalOptions);

            if (callback) {
                confirm.on('ok', callback);
            } else {
                confirm.on('ok', function() {
                    performTransition(element, null, pageRefresh);
                });
            }
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

        var dialogUrl = element.data('dialogUrl');
        if (!_.isEmpty(element.data('confirmation')) || !dialogUrl && !_.isEmpty(element.data('message'))) {
            showConfirmationModal(dialogUrl ? showDialog : null);
        } else if (dialogUrl) {
            showDialog();
        } else {
            performTransition(element, null, pageRefresh);
        }
    };
});
