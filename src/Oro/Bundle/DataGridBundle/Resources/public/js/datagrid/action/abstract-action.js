/*global define*/
define(['jquery', 'underscore', 'backbone', 'routing', 'oronavigation/js/navigation',
        'orotranslation/js/translator', 'oroui/js/mediator',
        'oroui/js/messenger', 'oroui/js/error', 'oroui/js/modal', '../action-launcher'
    ], function ($, _, Backbone, routing, Navigation, __, mediator, messenger, error, Modal, ActionLauncher) {
    'use strict';

    /**
     * Abstract action class. Subclasses should override execute method which is invoked when action is running.
     *
     * Triggers events:
     *  - "preExecute" before action is executed
     *  - "postExecute" after action is executed
     *
     * @export  orodatagrid/js/datagrid/action/abstract-action
     * @class   orodatagrid.datagrid.action.AbstractAction
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Function} */
        launcherConstructor: ActionLauncher,

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
        defaultMessages: {
            confirm_title: 'Execution Confirmation',
            confirm_content: 'Are you sure you want to do this?',
            confirm_ok: 'Yes, do it',
            success: 'Action performed.',
            error: 'Action is not performed.',
            empty_selection: 'Please, select item to perform action.'
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Object} [options.launcherOptions] Options for new instance of launcher object
         */
        initialize: function (options) {
            if (!options.datagrid) {
                throw new TypeError("'datagrid' is required");
            }
            this.datagrid = options.datagrid;
            this.messages = _.extend({}, this.defaultMessages, options.messages);
            this.launcherOptions = _.extend({}, this.launcherOptions, options.launcherOptions, {
                action: this
            });

            Backbone.View.prototype.initialize.apply(this, arguments);
        },

        /**
         * Creates launcher
         *
         * @param {Object=} options Launcher options
         * @return {orodatagrid.datagrid.ActionLauncher}
         */
        createLauncher: function (options) {
            options = options || {};
            if (_.isUndefined(options.icon) && !_.isUndefined(this.icon)) {
                options.icon = this.icon;
            }
            _.defaults(options, this.launcherOptions);
            return new (this.launcherConstructor)(options);
        },

        /**
         * Run action
         */
        run: function () {
            var options = {
                doExecute: true
            };
            this.trigger('preExecute', this, options);
            if (options.doExecute) {
                this.execute();
                this.trigger('postExecute', this, options);
            }
        },

        /**
         * Execute action
         */
        execute: function () {
            this._confirmationExecutor(_.bind(this.executeConfiguredAction, this));
        },

        executeConfiguredAction: function () {
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

        _confirmationExecutor: function (callback) {
            if (this.confirmation) {
                this.getConfirmDialog(callback).open();
            } else {
                callback();
            }
        },

        _handleWidget: function () {
            if (this.dispatched) {
                return;
            }
            this.frontend_options.url = this.frontend_options.url || this.getLinkWithParameters();
            this.frontend_options.title = this.frontend_options.title || this.label;
            require(['oro/' + this.frontend_handle + '-widget'],
            function(WidgetType) {
                var widget = new WidgetType(this.frontend_options);
                widget.render();
            });
        },

        _handleRedirect: function () {
            if (this.dispatched) {
                return;
            }
            var url = this.getLinkWithParameters(),
                navigation = Navigation.getInstance();
            if (navigation) {
                navigation.processRedirect({
                    fullRedirect: false,
                    location: url
                });
            } else {
                location.href = url;
            }
        },

        _handleAjax: function () {
            if (this.dispatched) {
                return;
            }
            if (this.reloadData) {
                this.datagrid.showLoading();
            }
            this._doAjaxRequest();
        },

        _doAjaxRequest: function () {
            $.ajax({
                url: this.getLink(),
                data: this.getActionParameters(),
                context: this,
                dataType: 'json',
                error: this._onAjaxError,
                success: this._onAjaxSuccess
            });
        },

        _onAjaxError: function (jqXHR) {
            error.handle({}, jqXHR, {enforce: true});
            if (this.reloadData) {
                this.datagrid.hideLoading();
            }
        },

        _onAjaxSuccess: function (data, textStatus, jqXHR) {
            if (this.reloadData) {
                this.datagrid.hideLoading();
                this.datagrid.collection.fetch();
            }
            this._showAjaxSuccessMessage(data);
        },

        _showAjaxSuccessMessage: function (data) {
            var defaultMessage = data.successful ? this.messages.success : this.messages.error,
                message = __(data.message || defaultMessage);
            if (message) {
                messenger.notificationFlashMessage(data.successful ? 'success' : 'error', message);
            }
        },

        /**
         * Get action url
         *
         * @return {String}
         * @private
         */
        getLink: function (parameters) {
            if (_.isUndefined(parameters)) {
                parameters = {};
            }
            return routing.generate(
                this.route,
                _.extend(
                    _.extend([], this.route_parameters),
                    parameters
                )
            );
        },

        /**
         * Get action url with parameters added.
         *
         * @returns {String}
         */
        getLinkWithParameters: function () {
            return this.getLink(this.getActionParameters());
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         */
        getActionParameters: function () {
            return {};
        },

        /**
         * Get view for confirm modal
         *
         * @return {oro.Modal}
         */
        getConfirmDialog: function (callback) {
            if (!this.confirmModal) {
                this.confirmModal = (new this.confirmModalConstructor({
                    title: __(this.messages.confirm_title),
                    content: __(this.messages.confirm_content),
                    okText: __(this.messages.confirm_ok)
                }));
                this.confirmModal.on('ok', callback);
            }
            return this.confirmModal;
        }
    });
});
