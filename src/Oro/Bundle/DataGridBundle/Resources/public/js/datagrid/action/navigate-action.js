define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    './model-action'
], function(_, __, mediator, ModelAction) {
    'use strict';

    /**
     * Navigate action. Changes window location to url, from getLink method
     *
     * @export  oro/datagrid/action/navigate-action
     * @class   oro.datagrid.action.NavigateAction
     * @extends oro.datagrid.action.ModelAction
     */
    const NavigateAction = ModelAction.extend({

        /**
         * If `true` then created launcher will be complete clickable link,
         * If `false` redirection will be delegated to execute method.
         *
         * @property {Boolean}
         */
        useDirectLauncherLink: true,

        /**
         * @inheritdoc
         */
        constructor: function NavigateAction(options) {
            NavigateAction.__super__.constructor.call(this, options);
        },

        /**
         * Initialize launcher options with url
         *
         * @param {Object} options
         * @param {Boolean} options.useDirectLauncherLink
         */
        initialize: function(options) {
            NavigateAction.__super__.initialize.call(this, options);

            if (options.useDirectLauncherLink) {
                this.useDirectLauncherLink = options.useDirectLauncherLink;
            }

            this.on('preExecute', this._preExecuteSubscriber.bind(this));

            if (options.parameters) {
                this.parameters = options.parameters;
            }

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
         *
         * @param {Object} options
         */
        execute: function(options) {
            let url = this.getLink();

            let key = this.datagrid.collection.stateHashKey();
            let value = this.datagrid.collection.stateHashValue();

            url = this.addUrlParameter(url, key, value);

            if (options.parameters) {
                for (key in options.parameters) {
                    if (options.parameters.hasOwnProperty(key)) {
                        value = options.parameters[key];
                        url = this.addUrlParameter(url, key, value);
                    }
                }
            }
            if (this.parameters) {
                for (key in this.parameters) {
                    if (this.parameters.hasOwnProperty(key)) {
                        value = this.parameters[key];
                        url = this.addUrlParameter(url, key, value);
                    }
                }
            }

            const {attributes = {}} = this.launcherOptions;
            if (!attributes.target) {
                mediator.execute('redirectTo', {url: url}, options);
            } else {
                window.open(url, attributes.target);
            }
        },

        /**
         * Trigger global event
         *
         * @private
         */
        _preExecuteSubscriber: function(action, options) {
            mediator.once('page:beforeError', function(xmlHttp, options) {
                let message;
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
