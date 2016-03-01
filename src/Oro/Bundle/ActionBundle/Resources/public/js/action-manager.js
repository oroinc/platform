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
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

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
            url: '',
            confirmation: false,
            showDialog: false,
            dialogOptions: {},
            messages: {},
            translates: {}
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
         * @type {Function}
         */
        confirmModalConstructor: DeleteConfirmation,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
        },

        /**
         * @param {jQuery.Event} e
         */
        execute: function(e) {
            if (this.options.confirmation) {
                this.showConfirmDialog(_.bind(this.doExecute, this, e));
            } else {
                this.doExecute(e);
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        doExecute: function(e) {
            if (this.options.showDialog) {
                var widget = new DialogWidget(this._getDialogOptions(this.options));

                Backbone.listenTo(widget, 'formSave', _.bind(function(response) {
                    widget.remove();
                    this.doResponse(response, e);
                }, this));

                widget.render();
            } else if (this.options.redirectUrl) {
                this.doRedirect(this.options.redirectUrl);
            } else {
                mediator.execute('showLoading');

                $.getJSON(this.options.url)
                    .done(_.bind(function(response) {
                        this.doResponse(response, e);
                    }, this))
                    .fail(function(jqXHR) {
                        var message = __('Could not perform action');
                        if (jqXHR.statusText) {
                            message += ': ' + jqXHR.statusText;
                        }

                        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                            message += ': ' + jqXHR.responseJSON.message;
                        }

                        mediator.execute('hideLoading');
                        messenger.notificationFlashMessage('error', message);
                    });
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
            var messages = _.defaults(this.options.messages, this.messages);

            if (!this.confirmModal) {
                this.confirmModal = (new this.confirmModalConstructor({
                    title: __(messages.confirm_title, $.extend({}, this.options.translates)),
                    content: __(messages.confirm_content, $.extend({}, this.options.translates)),
                    okText: __(messages.confirm_ok, $.extend({}, this.options.translates)),
                    cancelText: __(messages.confirm_cancel, $.extend({}, this.options.translates))
                }));
                Backbone.listenTo(this.confirmModal, 'ok', callback);
            } else {
                this.confirmModal.setContent(__(messages.confirm_content, $.extend({}, this.options.translates)));
            }

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
