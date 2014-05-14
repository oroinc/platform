/*global define*/
define(['underscore', 'orotranslation/js/translator', 'oroui/js/mediator', './model-action'
    ], function (_, __, mediator, ModelAction) {
    'use strict';

    /**
     * Navigate action. Changes window location to url, from getLink method
     *
     * @export  orodatagrid/js/datagrid/action/navigate-action
     * @class   orodatagrid.datagrid.action.NavigateAction
     * @extends orodatagrid.datagrid.action.ModelAction
     */
    return ModelAction.extend({

        /**
         * If `true` then created launcher will be complete clickable link,
         * If `false` redirection will be delegated to execute method.
         *
         * @property {Boolean}
         */
        useDirectLauncherLink: true,

        /**
         * Initialize launcher options with url
         *
         * @param {Object} options
         * @param {Boolean} options.useDirectLauncherLink
         */
        initialize: function (options) {
            ModelAction.prototype.initialize.apply(this, arguments);

            if (options.useDirectLauncherLink) {
                this.useDirectLauncherLink = options.useDirectLauncherLink;
            }

            this.on('preExecute', _.bind(this._preExecuteSubscriber, this));
            mediator.bind('grid_action:navigateAction:error', _.bind(this._processError, this));

            if (this.useDirectLauncherLink) {
                this.launcherOptions = _.extend({
                    link: this.getLink(),
                    runAction: false
                }, this.launcherOptions);
            }
        },

        /**
         * Execute redirect
         */
        execute: function () {
            window.location.href = this.getLink();
        },

        /**
         * Processes errors
         *
         * @private
         */
        _processError: function (action, HttpRequestStatus) {
            if (403 == HttpRequestStatus) {
                action.errorMessage = __('You do not have permission to this action.');
            }
        },

        /**
         * Trigger global event
         *
         * @private
         */
        _preExecuteSubscriber: function (action, options) {
            mediator.trigger('grid_action:navigateAction:preExecute', action, options);
        }
    });
});
