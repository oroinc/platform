/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    './model-action'
], function (_, __, mediator, ModelAction) {
    'use strict';

    var NavigateAction;

    /**
     * Navigate action. Changes window location to url, from getLink method
     *
     * @export  oro/datagrid/action/navigate-action
     * @class   oro.datagrid.action.NavigateAction
     * @extends oro.datagrid.action.ModelAction
     */
    NavigateAction = ModelAction.extend({

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
            NavigateAction.__super__.initialize.apply(this, arguments);

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
         *  - extends URL with grid state parameter
         */
        execute: function () {
            var url, key, value;
            url = this.getLink();

            key = this.datagrid.collection.stateHashKey();
            value = this.datagrid.collection.stateHashValue();

            url = this.addUrlParameter(url, key, value);
            mediator.execute('redirectTo', {url: url});
        },

        /**
         * Trigger global event
         *
         * @private
         */
        _preExecuteSubscriber: function (action, options) {
            mediator.once('page:beforeError', function (xmlHttp, options) {
                var message;
                if (403 === xmlHttp.status) {
                    options.stopPageProcessing = true;
                    message = __('You do not have permission to perform this action.');
                    mediator.execute('addMessage', 'error', message, {flash: true});
                }
            });
            mediator.trigger('grid_action:navigateAction:preExecute', action, options);
        }
    });

    return NavigateAction;
});
