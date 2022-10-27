define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const messenger = require('oroui/js/messenger');
    const widgetManager = require('oroui/js/widget-manager');
    const Backbone = require('backbone');
    const loadModules = require('oroui/js/app/services/load-modules');

    const ButtonManager = function(options) {
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
            /**
             *  Receives callback function that will be resolved with event object {result: <result>},
             *  where result * is a sign that was done changes through dialog or no
             */
            onDialogResult: null,
            executionUrl: '',
            requestMethod: 'GET',
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
         * @type {boolean}
         */
        isFormSaveInProgress: false,

        /**
         * @type {Function}
         */
        confirmModalConstructor: require('oroui/js/standart-confirmation'),

        confirmModalModulePromise: null,

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(_.pick(options, _.identity) || {}, this.options);

            if (this.options.confirmation.component) {
                this.confirmModalConstructor = null;
                this.confirmModalModulePromise = loadModules(this.options.confirmation.component);
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        execute: function(e) {
            if (this.hasConfirmDialog()) {
                if (this.confirmModalModulePromise) {
                    this.confirmModalModulePromise.then(function(confirmModalConstructor) {
                        if (this.disposed) {
                            return;
                        }

                        this.confirmModalConstructor = confirmModalConstructor;
                        this.showConfirmDialog(this.doExecute.bind(this, e));
                        this.confirmModalModulePromise = null;
                    }.bind(this));
                } else {
                    this.showConfirmDialog(this.doExecute.bind(this, e));
                }
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
            if (this.options.hasDialog) {
                const options = this._getDialogOptions();
                if (this.options.showDialog) {
                    loadModules(this.options.jsDialogWidget, function(Widget) {
                        const _widget = new Widget(options);
                        Backbone.listenTo(_widget, 'formSave', response => {
                            this.isFormSaveInProgress = true;
                            _widget.hide();
                            this.doResponse(response, e);
                            this.isFormSaveInProgress = false;
                        });
                        _widget.render();
                    }, this);
                } else {
                    this.doRedirect(options.url);
                }
            } else if (this.options.redirectUrl) {
                const {redirectUrlOptions: redirectOptions = {redirect: true}} = this.options;
                if (redirectOptions.newTab === true) {
                    redirectOptions.target = '_blank';
                }
                this.doRedirect(this.options.redirectUrl, redirectOptions);
            } else {
                mediator.execute('showLoading');
                if (this.isTokenProtected()) {
                    const ajaxOptions = {
                        type: 'POST',
                        data: this.options.executionTokenData,
                        dataType: 'json'
                    };
                    $.ajax(this.options.executionUrl, ajaxOptions)
                        .done(this.ajaxDone.bind(this))
                        .fail(this.ajaxFail.bind(this));
                } else {
                    if (this.options.requestMethod === 'POST') {
                        $.post(this.options.executionUrl, null, 'json')
                            .done(this.ajaxDone.bind(this))
                            .fail(this.ajaxFail.bind(this));
                    } else {
                        $.getJSON(this.options.executionUrl)
                            .done(this.ajaxDone.bind(this))
                            .fail(this.ajaxFail.bind(this));
                    }
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
            const response = _.defaults(jqXHR.responseJSON || {}, {
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
            const callback = () => {
                this._showFlashMessages(response);
            };

            if (response.redirectUrl) {
                const redirectOptions = {redirect: true};
                if (response.newTab === true) {
                    redirectOptions.target = '_blank';
                }

                mediator.once('page:afterChange', callback);
                this.doRedirect(response.redirectUrl, redirectOptions);
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

            if (_.isFunction(this.options.onDialogResult)) {
                this.options.onDialogResult({result: response.success || false});
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
                const messages = response.messages || {};

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
         * @param {Object=} options
         */
        doRedirect: function(redirectUrl, options = {}) {
            mediator.execute('redirectTo', {url: redirectUrl}, options)
                // in case redirect action was canceled -- remove loading mask
                .fail(() => mediator.execute('hideLoading'));
            if (options.target === '_blank') {
                mediator.execute('hideLoading');
            }
        },

        /**
         * @param {Object} response
         */
        doPageReload: function(response) {
            let pageReload = true;
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
            let options = {};

            if (!_.isEmpty(this.options.confirmation)) {
                const placeholders = _.mapObject(this.options.confirmation.message_parameters || {}, function(item) {
                    return _.isString(item) ? _.escape(item) : item;
                });

                options = _.defaults(_.omit(this.options.confirmation, 'component', 'message'), {
                    title: (this.options.confirmation.title || this.messages.confirm_title),
                    content: (this.options.confirmation.message || this.messages.confirm_content),
                    okText: (this.options.confirmation.ok || this.messages.confirm_ok),
                    cancelText: (this.options.confirmation.cancel || this.messages.confirm_cancel)
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

            this.confirmModal = new this.confirmModalConstructor(options);

            this.confirmModal
                .on('ok', callback)
                .on('hidden', function() {
                    delete this.confirmModal;
                }.bind(this));

            this.confirmModal.open();
        },

        /**
         * @return {Object}
         * @private
         */
        _getDialogOptions: function() {
            let options = {
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

            const additionalOptions = this.options.dialogOptions;
            if (additionalOptions) {
                if (additionalOptions.dialogOptions !== undefined) {
                    additionalOptions.dialogOptions = _.extend(
                        options.dialogOptions,
                        additionalOptions.dialogOptions
                    );
                }

                options = _.extend(options, additionalOptions);
            }

            options.dialogOptions.close = _.wrap(
                options.dialogOptions.close,
                this.onDialogClose.bind(this)
            );

            return options;
        },

        /**
         *
         * @param {function} wrappedOnCloseDialogCallback
         */
        onDialogClose: function(wrappedOnCloseDialogCallback) {
            if (_.isFunction(wrappedOnCloseDialogCallback)) {
                wrappedOnCloseDialogCallback();
            }

            if (_.isFunction(this.options.onDialogResult) && false === this.isFormSaveInProgress) {
                this.options.onDialogResult({result: false});
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.confirmModal) {
                this.confirmModal.dispose();
                delete this.confirmModal;
            }

            this.disposed = true;
        }
    });

    return ButtonManager;
});
