define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Modal = require('oroui/js/modal');
    const loadModules = require('oroui/js/app/services/load-modules');
    const Backbone = require('backbone');
    const performTransition = require('oroworkflow/js/transition-executor');

    /**
     * Transition button click handler
     *
     * @export  oroworkflow/js/transition-handler
     * @class   oroworkflow.WorkflowTransitionHandler
     */
    return function(pageRefresh) {
        const element = $(this);
        pageRefresh = _.isUndefined(pageRefresh) ? true : pageRefresh;

        const resetInProgress = function() {
            element.data('_in-progress', false);
        };

        const showDialog = function() {
            let dialogOptions = {
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
            const additionalOptions = element.data('dialog-options');
            if (additionalOptions) {
                if (!_.isUndefined(additionalOptions)) {
                    additionalOptions.dialogOptions = _.extend(
                        dialogOptions.dialogOptions,
                        additionalOptions.dialogOptions
                    );
                }
                dialogOptions = _.extend(dialogOptions, additionalOptions);
            }

            loadModules('oroworkflow/transition-dialog-widget', function(Widget) {
                const _widget = new Widget(dialogOptions);
                Backbone.listenTo(_widget, 'widgetRemove', () => {
                    resetInProgress();
                });

                _widget.render();
            });
        };

        /**
         * @param {function} callback
         */
        const showConfirmationModal = function(callback) {
            const message = element.data('message');
            let modalOptions = {};
            if (typeof message === 'string') {
                modalOptions = {
                    content: message,
                    title: element.data('transition-label')
                };
            } else {
                modalOptions = message;
            }

            const confirmation = element.data('confirmation') || {};
            let placeholders = {};
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

            const confirm = new Modal(modalOptions);

            callback = callback || function() {
                performTransition(element, null, pageRefresh);
            };

            confirm
                .on('ok', callback)
                .on('cancel', resetInProgress)
                .open();
        };

        if (element.data('_in-progress')) {
            return;
        }

        element.data('_in-progress', true);
        element.one('transitions_success', resetInProgress);
        element.one('transitions_failure', resetInProgress);

        const dialogUrl = element.data('dialogUrl');
        if (!_.isEmpty(element.data('confirmation')) || !dialogUrl && !_.isEmpty(element.data('message'))) {
            showConfirmationModal(dialogUrl ? showDialog : null);
        } else if (dialogUrl) {
            showDialog();
        } else {
            performTransition(element, null, pageRefresh);
        }
    };
});
