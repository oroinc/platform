/*jslint nomen:true, browser:true*/
/*global define*/
define([
    'underscore',
    './abstract-action'
], function (_, AbstractAction) {
    'use strict';

    var ModelAction, location;
    location = window.location;

    /**
     * Basic model action class.
     *
     * @export  oro/datagrid/action/model-action
     * @class   oro.datagrid.action.ModelAction
     * @extends oro.datagrid.action.AbstractAction
     */
    ModelAction = AbstractAction.extend({
        /** @property {Backbone.Model} */
        model: null,

        /** @property {String} */
        link: undefined,

        /** @property {Boolean} */
        backUrl: false,

        /** @property {String} */
        backUrlParameter: 'back',

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Backbone.Model} options.model Optional parameter
         * @throws {TypeError} If model is undefined
         */
        initialize: function (options) {
            var opts = options || {};

            if (!opts.model) {
                throw new TypeError("'model' is required");
            }
            this.model = opts.model;

            if (_.has(opts, 'backUrl')) {
                this.backUrl = opts.backUrl;
            }

            if (_.has(opts, 'backUrlParameter')) {
                this.backUrlParameter = opts.backUrlParameter;
            }

            ModelAction.__super__.initialize.apply(this, arguments);
        },

        /**
         * Get action link
         *
         * @return {String}
         * @throws {TypeError} If route is undefined
         */
        getLink: function () {
            var result, backUrl;
            if (!this.link) {
                throw new TypeError("'link' is required");
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
        addUrlParameter: function (url, parameterName, parameterValue) {
            var urlHash, sourceUrl, cl, urlParts, newQueryString, parameters, parameterParts, i,
                replaceDuplicates = true;
            if (url.indexOf('#') > 0) {
                cl = url.indexOf('#');
                urlHash = url.substring(url.indexOf('#'), url.length);
            } else {
                urlHash = '';
                cl = url.length;
            }
            sourceUrl = url.substring(0, cl);

            urlParts = sourceUrl.split("?");
            newQueryString = "";

            if (urlParts.length > 1) {
                parameters = urlParts[1].split("&");
                for (i = 0; (i < parameters.length); i += 1) {
                    parameterParts = parameters[i].split("=");
                    if (!(replaceDuplicates && parameterParts[0] === parameterName)) {
                        if (newQueryString === "") {
                            newQueryString = "?";
                        } else {
                            newQueryString += "&";
                        }
                        newQueryString += parameterParts[0] + "=" + (parameterParts[1] || '');
                    }
                }
            }
            if (newQueryString === "") {
                newQueryString = "?";
            }
            if (newQueryString !== "" && newQueryString !== '?') {
                newQueryString += "&";
            }
            newQueryString += parameterName + "=" + (parameterValue || '');
            return urlParts[0] + newQueryString + urlHash;
        }
    });

    return ModelAction;
});
