/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/messenger',
    'oroui/js/mediator',
    './model-action'
], function (_, __, messenger, mediator, ModelAction) {
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
            mediator.execute('redirectTo', {url: this.getLink()});
        },

        /**
         * Trigger global event
         *
         * @private
         */
        _preExecuteSubscriber: function (action, options) {
            mediator.once('page:beforeError', function (xmlHttp, options) {
                if (403 === xmlHttp.status) {
                    options.stopPageProcessing = true;
                    messenger.notificationFlashMessage('error', __('You do not have permission to perform this action.'));
                }
            });
            mediator.trigger('grid_action:navigateAction:preExecute', action, options);
        }
    });
});
