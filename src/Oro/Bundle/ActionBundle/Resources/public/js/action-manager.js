/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var widgetManager = require('oroui/js/widget-manager');
    var Backbone = require('backbone');
    var DialogWidget = require('oro/dialog-widget');

    var ActionManager = function(options) {
        this.initialize(options);
    };

    _.extend(ActionManager.prototype, {

        /**
         * @type {Object}
         */
        options: {
            widgetAlias: 'action_buttons_widget',
            redirectUrl: '',
            dialogUrl: '',
            executionUrl: '',
            confirmation: {},
            showDialog: false,
            hasDialog: false,
            dialogOptions: {}
        },

        /**
         * @type {Object}
         */
        messages: {
            confirm_title: 'oro.action.confirm_title',
            confirm_content: 'oro.action.confirm_content',
            confirm_ok: 'Yes, Delete',
            confirm_cancel: 'Cancel'
        },

        /**
         * @type {Object}
         */
        confirmModal: null,

        /**
         * @type {String}
         */
        confirmComponent: 'oroui/js/delete-confirmation',

        /**
         * @type {Function}
         */
        confirmModalConstructor: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(_.pick(options, _.identity) || {}, this.options);

            this.confirmModalConstructor = require(this.options.confirmation.component || this.confirmComponent);
        },

        /**
         * @param {jQuery.Event} e
         */
        execute: function(e) {
            if (!_.isEmpty(this.options.confirmation)) {
                this.showConfirmDialog(_.bind(this.doExecute, this, e));
            } else {
                this.doExecute(e);
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        doExecute: function(e) {
            if (this.options.hasDialog) {
                var options = this._getDialogOptions(this.options);
                if (this.options.showDialog) {
                    var widget = new DialogWidget(options);

                    Backbone.listenTo(widget, 'formSave', _.bind(function(response) {
                        widget.remove();
                        this.doResponse(response, e);
                    }, this));

                    widget.render();
                } else {
                    this.doRedirect(options.url);
                }
            } else if (this.options.redirectUrl) {
                this.doRedirect(this.options.redirectUrl);
            } else {
                mediator.execute('showLoading');

                $.getJSON(this.options.executionUrl)
                    .done(_.bind(function(response) {
                        this.doResponse(response, e);
                    }, this))
                    .fail(_.bind(function(jqXHR) {
                        var response = _.defaults(jqXHR.responseJSON, {
                            success: false,
                            message: ''
                        });

                        response.message = __('Could not perform action') + ': ' + response.message;

                        this.doResponse(response);
                    }, this));
            }
        },

        /**
         * @param {Object} response
         * @param {jQuery.Event} e
         */
        doResponse: function(response, e) {
            mediator.execute('hideLoading');

            if (response.flashMessages) {
                _.each(response.flashMessages, function(messages, type) {
                    _.each(messages, function(message) {
                        messenger.notificationFlashMessage(type, message);
                    });
                });
            }

            if (!response.success) {
                var messages = response.messages || {};

                if (_.isEmpty(messages)) {
                    messenger.notificationFlashMessage('error', response.message);
                } else {
                    _.each(messages, function(submessage) {
                        messenger.notificationFlashMessage('error', response.message + ': ' + submessage);
                    });
                }
            }

            if (response.redirectUrl) {
                if (e !== undefined) {
                    e.stopImmediatePropagation();
                }
                this.doRedirect(response.redirectUrl);
            } else if (response.refreshGrid) {
                _.each(response.refreshGrid, function(gridname) {
                    mediator.trigger('datagrid:doRefresh:' + gridname);
                });
                this.doWidgetReload();
            } else {
                this.doPageReload();
            }
        },

        /**
         * @param {String} redirectUrl
         */
        doRedirect: function(redirectUrl) {
            mediator.execute('redirectTo', {url: redirectUrl}, {redirect: true});
        },

        doPageReload: function() {
            mediator.execute('refreshPage');
        },

        doWidgetReload: function() {
            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                widget.render();
            });
        },

        /**
         * @param {function} callback
         * @return {oroui.Modal}
         */
        showConfirmDialog: function(callback) {
            var placeholders = this.options.confirmation.message_parameters || {};

            var messages = {
                title: (this.options.confirmation.title || this.messages.confirm_title),
                content: (this.options.confirmation.message || this.messages.confirm_content),
                okText: (this.options.confirmation.okText || this.messages.confirm_ok),
                cancelText: (this.options.confirmation.cancelText || this.messages.confirm_cancel)
            };

            _.each(messages, function(item, key, list) {
                list[key] = __(item, $.extend({}, placeholders));
            });

            this.confirmModal = (new this.confirmModalConstructor(messages));
            Backbone.listenTo(this.confirmModal, 'ok', callback);

            this.confirmModal.open();
        },

        /**
         * @return {Object}
         * @private
         */
        _getDialogOptions: function() {
            var options = {
                title: 'action',
                url: this.options.dialogUrl,
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

            var additionalOptions = this.options.dialogOptions;
            if (additionalOptions) {
                if (additionalOptions.dialogOptions !== undefined) {
                    additionalOptions.dialogOptions = _.extend(
                        options.dialogOptions,
                        additionalOptions.dialogOptions
                    );
                }

                options = _.extend(options, additionalOptions);
            }

            return options;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.confirmModal;
        }
    });

    return ActionManager;
});
