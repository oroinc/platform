define(function(require) {
    'use strict';

    var AbstractAction;
    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var routing = require('routing');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var Modal = require('oroui/js/modal');
    var ActionLauncher = require('orodatagrid/js/datagrid/action-launcher');
    var Chaplin = require('chaplin');

    /**
     * Abstract action class. Subclasses should override execute method which is invoked when action is running.
     *
     * Triggers events:
     *  - "preExecute" before action is executed
     *  - "postExecute" after action is executed
     *
     * @export  oro/datagrid/action/abstract-action
     * @class   oro.datagrid.action.AbstractAction
     * @extends Backbone.View
     */
    AbstractAction = Backbone.View.extend({
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
         * @inheritDoc
         */
        constructor: function AbstractAction() {
            AbstractAction.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Object} [options.launcherOptions] Options for new instance of launcher object
         */
        initialize: function(options) {
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

            AbstractAction.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.datagrid;
            delete this.launcherOptions;
            delete this.messages;
            delete this.confirmModal;
            AbstractAction.__super__.dispose.apply(this, arguments);
        },

        /**
         * Creates launcher
         *
         * @param {Object=} options Launcher options
         * @return {orodatagrid.datagrid.ActionLauncher}
         */
        createLauncher: function(options) {
            var launcher;
            options = options || {};
            if (_.isUndefined(options.icon) && !_.isUndefined(this.icon)) {
                options.icon = this.icon;
            }
            _.defaults(options, this.launcherOptions);
            launcher = new (this.launcher)(options);
            this.launcherInstanse = launcher;
            // schedule dispose
            this.subviews.push(launcher);
            return launcher;
        },

        /**
         * Run action
         *
         * @param {Object} options
         */
        run: function(options) {
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
        execute: function() {
            this._confirmationExecutor(_.bind(this.executeConfiguredAction, this));
        },

        executeConfiguredAction: function() {
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
        _getDefaultMessages: function() {
            var defaultMessages = Chaplin.utils.getAllPropertyVersions(this, 'defaultMessages');
            defaultMessages.unshift({});
            defaultMessages = _.extend.apply(_, defaultMessages);
            return defaultMessages;
        },

        _confirmationExecutor: function(callback) {
            if (this.confirmation) {
                this.getConfirmDialog(callback).open();
            } else {
                callback();
            }
        },

        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }
            this.frontend_options = this.frontend_options || {};
            this.frontend_options.url = this.getLinkWithParameters();
            this.frontend_options.title = this.frontend_options.title || this.label;
            require(['oro/' + this.frontend_handle + '-widget'], _.bind(function(WidgetType) {
                var widget = new WidgetType(this.frontend_options);
                widget.render();
            }, this));
        },

        _handleRedirect: function() {
            if (this.dispatched) {
                return;
            }
            var url = this.getLinkWithParameters();
            mediator.execute('redirectTo', {url: url}, {redirect: true});
        },

        _handleAjax: function() {
            if (this.dispatched) {
                return;
            }
            if (this.reloadData) {
                this.datagrid.showLoading();
            }
            this._doAjaxRequest();
        },

        _doAjaxRequest: function() {
            $.ajax({
                url: this.getLink(),
                data: this.getActionParameters(),
                context: this,
                dataType: 'json',
                type: this.requestType,
                error: this._onAjaxError,
                success: this._onAjaxSuccess
            });
        },

        _onAjaxError: function(jqXHR) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
            }
        },

        _onAjaxSuccess: function(data) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
                this.datagrid.collection.fetch({reset: true});
            }
            this._showAjaxSuccessMessage(data);
        },

        _showAjaxSuccessMessage: function(data) {
            var defaultMessage = data.successful ? this.messages.success : this.messages.error;
            var type = data.successful ? 'success' : 'error';
            var message = data.message || __(defaultMessage);
            if (message) {
                mediator.execute('showFlashMessage', type, message);
            }
        },

        /**
         * Get action url
         *
         * @return {String}
         * @private
         */
        getLink: function(parameters) {
            if (_.isUndefined(parameters)) {
                parameters = {};
            }

            // Add original query parameters as them may be valuable for backend logic
            var originalUrl = this.datagrid.collection.url;
            var originalRequestParameters = tools.unpackFromQueryString(
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
        getLinkWithParameters: function() {
            return this.getLink(this.getActionParameters());
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         */
        getActionParameters: function() {
            return {};
        },

        /**
         * Get view for confirm modal
         *
         * @return {oroui.Modal}
         */
        getConfirmDialog: function(callback) {
            if (!this.confirmModal) {
                this.confirmModal = (new this.confirmModalConstructor({
                    title: __(this.messages.confirm_title),
                    content: this.getConfirmContentMessage(),
                    okText: __(this.messages.confirm_ok),
                    cancelText: __(this.messages.confirm_cancel)
                }));
                this.listenTo(this.confirmModal, 'ok', callback);

                this.subviews.push(this.confirmModal);
            }
            return this.confirmModal;
        },

        /**
         * Get confirm content message
         *
         * @return {String}
         */
        getConfirmContentMessage: function() {
            return __(this.messages.confirm_content, this.messages.confirm_content_params);
        },

        /**
         * Get ajax request type
         *
         * @return {String}
         */
        getRequestType: function() {
            return this.requestType;
        }

    });

    return AbstractAction;
});
