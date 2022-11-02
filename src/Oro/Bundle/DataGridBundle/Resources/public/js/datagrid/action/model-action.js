define([
    'underscore',
    './abstract-action',
    'orodatagrid/js/url-helper'
], function(_, AbstractAction, UrlHelper) {
    'use strict';

    const location = window.location;

    /**
     * Basic model action class.
     *
     * @export  oro/datagrid/action/model-action
     * @class   oro.datagrid.action.ModelAction
     * @extends oro.datagrid.action.AbstractAction
     */
    const ModelAction = AbstractAction.extend({
        /** @property {Backbone.Model} */
        model: null,

        /** @property {String} */
        link: undefined,

        /** @property {String} */
        title: undefined,

        /** @property {String} */
        ariaLabel: undefined,

        /** @property {Boolean} */
        backUrl: false,

        /** @property {String} */
        backUrlParameter: 'back',

        /**
         * @inheritdoc
         */
        constructor: function ModelAction(options) {
            ModelAction.__super__.constructor.call(this, options);
        },

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Backbone.Model} options.model Optional parameter
         * @throws {TypeError} If model is undefined
         */
        initialize: function(options) {
            const opts = options || {};

            if (!opts.model) {
                throw new TypeError('"model" is required');
            }
            this.model = opts.model;

            if (_.has(opts, 'backUrl')) {
                this.backUrl = opts.backUrl;
            }

            if (_.has(opts, 'backUrlParameter')) {
                this.backUrlParameter = opts.backUrlParameter;
            }

            ModelAction.__super__.initialize.call(this, options);

            const title = this.model.get(this.title);
            if (title) {
                this.launcherOptions.title = title;
            }

            const ariaLabel = this.model.get(this.ariaLabel);
            if (ariaLabel) {
                this.launcherOptions.ariaLabel = ariaLabel;
            }
        },

        /**
         * Get action link
         *
         * @return {String}
         * @throws {TypeError} If route is undefined
         */
        getLink: function() {
            let result;
            let backUrl;
            if (!this.link) {
                throw new TypeError('"link" is required');
            }

            if (this.model.has(this.link)) {
                result = this.model.get(this.link);
            } else {
                result = this.link;
            }

            if (this.backUrl) {
                backUrl = _.isBoolean(this.backUrl) ? location.href : this.backUrl;
                backUrl = encodeURIComponent(backUrl);
                result = this.addUrlParameter(result, this.backUrlParameter, backUrl);
            }

            return result;
        },

        /**
         * Add parameter to URL
         *
         * @param {string} url
         * @param {string} parameterName
         * @param {string} parameterValue
         * @return {string}
         * @protected
         */
        addUrlParameter: function(url, parameterName, parameterValue) {
            return UrlHelper.addUrlParameter(url, parameterName, parameterValue);
        }
    });

    return ModelAction;
});
