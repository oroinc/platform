define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const loadModules = require('oroui/js/app/services/load-modules');
    const tools = require('oroui/js/tools');
    const Modal = require('oroui/js/modal');
    const ActionLauncher = require('orodatagrid/js/datagrid/action-launcher');
    const Chaplin = require('chaplin');

    /**
     * Abstract action class. Subclasses should override execute method which is invoked when action is running.
     *
     * Triggers events:
     *  - "preExecute" before action is executed
     *  - "postExecute" after action is executed
     *
     * @export  oro/datagrid/action/abstract-action
     * @class   oro.datagrid.action.AbstractAction
     * @extends BaseView
     */
    const AbstractAction = BaseView.extend({
        /** @property {Function} */
        launcher: ActionLauncher,

        /** @property {String} */
        name: null,

        /** @property {orodatagrid.datagrid.Grid} */
        datagrid: null,

        /** @property {string} */
        route: null,

        /** @property {Object} */
        route_parameters: null,

        /** @property {Boolean} */
        confirmation: false,

        /** @property {Function} */
        confirmModalConstructor: Modal,

        /** @property {String} */
        frontend_type: null,

        /** @property {String} */
        frontend_handle: null,

        /** @property {Object} */
        frontend_options: null,

        /** @property {String} */
        identifierFieldName: 'id',

        /** @property {Boolean} */
        dispatched: false,

        /** @property {Boolean} */
        reloadData: true,

        /** @property {Object} */
        messages: null,

        /** @property {Object} */
        launcherOptions: null,

        /** @property {String} */
        requestType: 'GET',

        /** @property {Number} */
        order: 500,

        /** @property {Object} */
        defaultMessages: {
            confirm_title: 'Execution Confirmation',
            confirm_content: 'Are you sure you want to do this?',
            confirm_content_params: {},
            confirm_ok: 'Yes, do it',
            confirm_cancel: 'Cancel',
            success: 'Action performed.',
            error: 'Action is not performed.',
            empty_selection: 'Please, select item to perform action.'
        },

        /** @property {Object} */
        configuration: {},

        /**
         * @inheritdoc
         */
        constructor: function AbstractAction(options) {
            AbstractAction.__super__.constructor.call(this, options);
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Object} [options.launcherOptions] Options for new instance of launcher object
         */
        initialize(options) {
            if (!options.datagrid) {
                throw new TypeError('"datagrid" is required');
            }
            if (options.configuration) {
                this.configuration = $.extend(true, {}, this.configuration, options.configuration);
            }
            if (options.requestType) {
                this.requestType = options.requestType;
            }
            if (options.order) {
                this.order = options.order;
            }
            this.subviews = [];
            this.datagrid = options.datagrid;
            // make own messages property from prototype
            this.messages = _.extend({}, this._getDefaultMessages(), this.messages);
            // make own launcherOptions property from prototype
            this.launcherOptions = $.extend(true, {}, this.launcherOptions, options.launcherOptions, {
                action: this
            });

            AbstractAction.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose() {
            if (this.disposed) {
                return;
            }
            delete this.datagrid;
            delete this.launcherOptions;
            delete this.messages;

            if (this.confirmModal) {
                this.confirmModal.dispose();
                delete this.confirmModal;
            }

            AbstractAction.__super__.dispose.call(this);
        },

        /**
         * Creates launcher
         *
         * @param {Object=} options Launcher options
         * @return {orodatagrid.datagrid.ActionLauncher}
         */
        createLauncher(options) {
            options = options || {};
            if (_.isUndefined(options.icon) && !_.isUndefined(this.icon)) {
                options.icon = this.icon;
            }
            _.defaults(options, this.launcherOptions);
            const launcher = new (this.launcher)(options);
            this.launcherInstance = launcher;
            // schedule dispose
            this.subviews.push(launcher);
            return launcher;
        },

        /**
         * Run action
         *
         * @param {Object} options
         */
        run(options) {
            options = _.defaults(options, {
                doExecute: true
            });
            this.trigger('preExecute', this, options);
            if (options.doExecute) {
                this.execute(options);
                this.trigger('postExecute', this, options);
            }
        },

        /**
         * Execute action
         */
        execute() {
            this._confirmationExecutor(this.executeConfiguredAction.bind(this));
        },

        executeConfiguredAction() {
            switch (this.frontend_handle) {
                case 'ajax':
                    this._handleAjax();
                    break;
                case 'redirect':
                    this._handleRedirect();
                    break;
                default:
                    this._handleWidget();
            }
        },

        /**
         * Collect and merge default messages from prototype chain of the action
         *
         * @returns {Object}
         * @protected
         */
        _getDefaultMessages() {
            let defaultMessages = Chaplin.utils.getAllPropertyVersions(this, 'defaultMessages');
            defaultMessages.unshift({});
            defaultMessages = _.extend(...defaultMessages);
            return defaultMessages;
        },

        _confirmationExecutor(callback) {
            if (this.confirmation) {
                this.getConfirmDialog(callback).open();
            } else {
                callback();
            }
        },

        async _handleWidget() {
            if (this.dispatched) {
                return;
            }
            this.frontend_options = this.frontend_options || {};
            this.frontend_options.url = this.getLinkWithParameters();
            this.frontend_options.title = this.frontend_options.title || this.label;
            return loadModules('oro/' + this.frontend_handle + '-widget')
                .then(WidgetType => {
                    const widget = new WidgetType(this.frontend_options);
                    widget.render();
                    return widget;
                });
        },

        _handleRedirect() {
            if (this.dispatched) {
                return;
            }
            const url = this.getLinkWithParameters();
            mediator.execute('redirectTo', {url: url}, {redirect: true});
        },

        async _handleAjax() {
            if (this.dispatched) {
                return;
            }
            if (this.reloadData) {
                this.datagrid.showLoading();
            }
            return this._doAjaxRequest();
        },

        async _doAjaxRequest() {
            return $.ajax({
                url: this.getLink(),
                data: this.getActionParameters(),
                context: this,
                dataType: 'json',
                type: this.requestType,
                error: this._onAjaxError,
                success: this._onAjaxSuccess
            });
        },

        _onAjaxError(jqXHR) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
            }
        },

        _onAjaxSuccess(data) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
                this.datagrid.collection.fetch({reset: true});
            }
            this._showAjaxSuccessMessage(data);
        },

        _showAjaxSuccessMessage(data) {
            const defaultMessage = data.successful ? this.messages.success : this.messages.error;
            const type = data.successful ? 'success' : 'error';
            const messageOptions = data[`${type}MessageOptions`] || this[`${type}MessageOptions`] || {};
            const message = data.message || __(defaultMessage);
            if (message) {
                mediator.execute('showFlashMessage', type, message, messageOptions);
            }
        },

        /**
         * Get action url
         *
         * @return {string=}
         * @private
         */
        getLink(parameters = {}) {
            // Add original query parameters as them may be valuable for backend logic
            const originalUrl = this.datagrid.collection.url;
            const originalRequestParameters = tools.unpackFromQueryString(
                originalUrl.substring(originalUrl.indexOf('?'), originalUrl.length)
            );

            return routing.generate(
                this.route,
                _.extend(
                    _.extend([], this.route_parameters),
                    $.extend(true, {}, originalRequestParameters, parameters)
                )
            );
        },

        /**
         * Get action url with parameters added.
         *
         * @returns {String}
         */
        getLinkWithParameters() {
            return this.getLink(this.getActionParameters());
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         */
        getActionParameters() {
            return {};
        },

        /**
         * Get view for confirm modal
         *
         * @return {oroui.Modal}
         */
        getConfirmDialog(callback) {
            if (this.confirmModal) {
                this.confirmModal.dispose();
                delete this.confirmModal;
            }

            this.confirmModal = new this.confirmModalConstructor(this.getConfirmDialogOptions());

            this.confirmModal
                .on('ok', callback)
                .on('hidden', function() {
                    delete this.confirmModal;
                }.bind(this));

            return this.confirmModal;
        },

        getConfirmDialogOptions() {
            const options = {
                title: __(this.messages.confirm_title),
                content: this.getConfirmContentMessage(),
                okText: __(this.messages.confirm_ok),
                cancelText: __(this.messages.confirm_cancel)
            };

            return options;
        },

        /**
         * Get confirm content message
         *
         * @return {String}
         */
        getConfirmContentMessage() {
            return __(this.messages.confirm_content, this.messages.confirm_content_params);
        },

        /**
         * Get ajax request type
         *
         * @return {String}
         */
        getRequestType() {
            return this.requestType;
        }

    });

    return AbstractAction;
});
