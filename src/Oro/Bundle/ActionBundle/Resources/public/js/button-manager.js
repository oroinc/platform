define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var widgetManager = require('oroui/js/widget-manager');
    var Backbone = require('backbone');
    var tools = require('oroui/js/tools');
    require('oroui/js/standart-confirmation'); // preload default confirmation dialog module

    var ButtonManager = function(options) {
        this.initialize(options);
    };

    _.extend(ButtonManager.prototype, {
        /**
         * @type {Object}
         */
        options: {
            executionTokenData: null,
            widgetAlias: 'action_buttons_widget',
            fullRedirect: false,
            redirectUrl: '',
            dialogUrl: '',
            executionUrl: '',
            confirmation: {},
            message: {},
            showDialog: false,
            hasDialog: false,
            dialogOptions: {},
            jsDialogWidget: 'oro/dialog-widget'
        },

        /**
         * @type {Object}
         */
        messages: {
            confirm_title: 'oro.action.confirm_title',
            confirm_content: 'oro.action.confirm_content',
            confirm_ok: 'Yes',
            confirm_cancel: 'Cancel'
        },

        /**
         * @type {Object}
         */
        confirmModal: null,

        /**
         * @type {String}
         */
        confirmComponent: 'oroui/js/standart-confirmation',

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
            if (this.hasConfirmDialog()) {
                this.showConfirmDialog(_.bind(this.doExecute, this, e));
            } else {
                this.doExecute(e);
            }
        },

        hasConfirmDialog: function() {
            return !_.isEmpty(this.options.confirmation) || !_.isEmpty(this.options.message);
        },

        /**
         * @param {jQuery.Event} e
         */
        doExecute: function(e) {
            var self = this;
            if (this.options.hasDialog) {
                var options = this._getDialogOptions();
                if (this.options.showDialog) {
                    tools.loadModules(this.options.jsDialogWidget, function(Widget) {
                        var _widget = new Widget(options);
                        Backbone.listenTo(_widget, 'formSave', _.bind(function(response) {
                            _widget.hide();
                            self.doResponse(response, e);
                        }, this));

                        _widget.render();
                    });
                } else {
                    this.doRedirect(options.url);
                }
            } else if (this.options.redirectUrl) {
                this.doRedirect(this.options.redirectUrl);
            } else {
                mediator.execute('showLoading');
                if (this.isTokenProtected()) {
                    var ajaxOptions = {
                        type: 'POST',
                        data: this.options.executionTokenData,
                        dataType: 'json'
                    };
                    $.ajax(this.options.executionUrl, ajaxOptions)
                        .done(_.bind(this.ajaxDone, this))
                        .fail(_.bind(this.ajaxFail, this));
                } else {
                    $.getJSON(this.options.executionUrl)
                        .done(_.bind(this.ajaxDone, this))
                        .fail(_.bind(this.ajaxFail, this));
                }
            }
        },

        /**
         * Ajax done handler
         *
         * @param response
         * @param e
         */
        ajaxDone: function(response, e) {
            this.doResponse(response, e);
        },

        /**
         * Ajax fail handler
         *
         * @param jqXHR
         */
        ajaxFail: function(jqXHR) {
            var response = _.defaults(jqXHR.responseJSON || {}, {
                success: false,
                message: this.options.action ? this.options.action.label : ''
            });

            response.message = __('Could not perform action') + ': ' + response.message;

            this.doResponse(response);
        },

        /**
         * Returns whether this manager was configured to use token protection for executing actions or not.
         * @returns {boolean}
         */
        isTokenProtected: function() {
            return Boolean(this.options.executionTokenData);
        },

        /**
         * @param {Object} response
         * @param {jQuery.Event} e
         */
        doResponse: function(response, e) {
            var callback = _.bind(function() {
                this._showFlashMessages(response);
            }, this);

            if (response.redirectUrl) {
                mediator.once('page:afterChange', callback);
                this.doRedirect(response.redirectUrl);
            } else if (response.refreshGrid) {
                mediator.execute('hideLoading');
                _.each(response.refreshGrid, function(gridname) {
                    mediator.trigger('datagrid:doRefresh:' + gridname);
                });
                this.doWidgetReload();
                this._showFlashMessages(response);
            } else {
                mediator.once('page:afterChange', callback);

                this.doPageReload(response);
            }
        },

        /**
         * @param {Object} response
         */
        _showFlashMessages: function(response) {
            if (response.flashMessages) {
                _.each(response.flashMessages, function(messages, type) {
                    _.each(messages, function(message) {
                        messenger.notificationFlashMessage(type, message);
                    });
                });
            }

            if (!response.success) {
                mediator.execute('hideLoading');
                var messages = response.messages || {};

                if (_.isEmpty(messages) && response.message) {
                    messenger.notificationFlashMessage('error', response.message);
                } else {
                    _.each(messages, function(submessage) {
                        messenger.notificationFlashMessage('error', response.message + ': ' + submessage);
                    });
                }
            }
        },

        /**
         * @param {String} redirectUrl
         */
        doRedirect: function(redirectUrl) {
            mediator.execute('redirectTo', {url: redirectUrl}, {redirect: true});
        },

        /**
         * @param {Object} response
         */
        doPageReload: function(response) {
            var pageReload = true;
            if (response.pageReload !== undefined) {
                pageReload = Boolean(response.pageReload);
            }

            if (pageReload) {
                mediator.execute('refreshPage', {fullRedirect: this.options.fullRedirect});
            } else {
                mediator.execute('hideLoading');
            }
        },

        doWidgetReload: function() {
            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                widget.render();
            });
        },

        /**
         * @param {function} callback
         */
        showConfirmDialog: function(callback) {
            var options = {};
            if (!_.isEmpty(this.options.confirmation)) {
                var placeholders = this.options.confirmation.message_parameters || {};

                options = _.defaults(_.omit(this.options.confirmation, 'component', 'message'), {
                    title: this.messages.confirm_title,
                    content: (this.options.confirmation.message || this.messages.confirm_content),
                    okText: this.messages.confirm_ok,
                    cancelText: this.messages.confirm_cancel
                });

                _.each(options, function(item, key, list) {
                    list[key] = _.isString(item) ? __(item, $.extend({}, placeholders)) : item;
                });
            } else {
                options = {
                    content: this.options.message.content || __(this.messages.confirm_content),
                    title: this.options.message.title || __(this.messages.confirm_title),
                    okText: this.options.message.okText || __(this.messages.confirm_ok),
                    cancelText: this.options.message.cancelText || __(this.messages.confirm_cancel)
                };
            }

            this.confirmModal = (new this.confirmModalConstructor(options));
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

    return ButtonManager;
});
