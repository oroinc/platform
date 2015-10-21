/** @lends RouteModel */
define(function(require) {
    'use strict';

    /**
     * Abstraction of route
     *
     * Basic usage:
     * ```javascript
     * var route = new RouteModel({
     *     // route specification
     *     routeName: 'oro_api_comment_get_items',
     *     routeQueryParameterNames: ['page', 'limit'],
     *
     *     // required parameters for route path
     *     relationId: 123,
     *     relationClass: 'Some_Class'
     *
     *     // default query parameter
     *     limit: 10
     * });
     *
     * // returns api/rest/latest/relation/Some_Class/123/comment?limit=10
     * route.getUrl();
     *
     * // returns api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2
     * route.getUrl({page: 2})
     * ```
     *
     * @class
     * @augment BaseModel
     * @exports RouteModel
     */
    var RouteModel;
    var _ = require('underscore');
    var routing = require('routing');
    var BaseModel = require('./base/model');

    RouteModel = BaseModel.extend(/** @exports RouteModel.prototype */{
        /**
         * Route name cache prepared for
         *
         * @member {String}
         */
        _cachedRouteName: null,

        /**
         * Cached required parameters
         *
         * @member {Array.<String>}
         */
        _requiredParametersCache: null,

        /**
         * @inheritDoc
         * @member {Object}
         */
        defaults: function() {
            return /** lends RouteModel.attributes */ {
                /**
                 * Name of the route
                 * @type {string}
                 */
                routeName: null,

                /**
                 * List of acceptable query parameter names for this route
                 * @type {Array.<string>}
                 */
                routeQueryParameterNames: []
            };
        },

        /**
         * Return list of parameter names required by this route (Route parameters are required to build valid url, all
         * query parameters assumed as filters and are not required)
         *
         * E.g. for route `api/rest/latest/<relationClass>/<relationId/comments`
         * This function will return `['relationClass', 'relationId']`
         *
         * @returns {Array.<string>}
         */
        getRequiredParameters: function() {
            if (!this._requiredParametersCache || this.get('routeName') !== this._cachedRouteName) {
                if (!this.get('routeName')) {
                    throw new Error('routeName must be specified');
                }
                var route = routing.getRoute(this.get('routeName'));
                var variableTokens = _.filter(route.tokens, function(tokenPart) {
                    return tokenPart[0] === 'variable';
                });
                var routeParameters = _.map(variableTokens, function(tokenPart) {
                    return tokenPart[3];
                });
                this._requiredParametersCache = _.uniq(routeParameters);
                this._cachedRouteName = this.get('routeName');
            }
            return this._requiredParametersCache;
        },

        /**
         * Return list of parameter names accepted by this route.
         * Includes both query and route parameters
         *
         * E.g. for route `api/rest/latest/<relationClass>/<relationId/comments?page=<page>&limit=<limit>`
         * this function will return `['relationClass', 'relationId', 'page', 'limit']`
         *
         * @returns {Array.<string>}
         */
        getAcceptableParameters: function() {
            var routeParameters = this.getRequiredParameters();
            routeParameters.push.apply(routeParameters, this.get('routeQueryParameterNames'));
            return _.uniq(routeParameters);
        },

        /**
         * Returns url defined by this model
         *
         * @param parameters {Object=} parameters to override
         * @returns {string} route url
         */
        getUrl: function(parameters) {
            var routeParameters = _.extend(this.toJSON(), parameters);
            var acceptableParameters = this.getAcceptableParameters();
            return routing.generate(this.get('routeName'), _.pick(routeParameters, acceptableParameters));
        },

        /**
         * Validates parameters list
         *
         * @param parameters {Object=} parameters to build url
         * @returns {boolean} true, if parameters are valid
         */
        validateParameters: function(parameters) {
            var routeParameters = _.extend(this.toJSON(), parameters);
            var requiredParameters = this.getRequiredParameters();

            for (var i = 0; i < requiredParameters.length; i++) {
                var parameterName = requiredParameters[i];
                var parameterValue = routeParameters[parameterName];
                if (_.isString(parameterValue)) {
                    if (parameterValue !== '') {
                        continue;
                    } else {
                        return false;
                    }
                }
                if (_.isNumber(parameterValue)) {
                    if (!isNaN(parameterValue)) {
                        continue;
                    } else {
                        return false;
                    }
                }
                if (parameterValue !== null && String(parameterValue) !== '[object Object]') {
                    continue;
                } else {
                    return false;
                }
            }
            return true;
        }
    });

    return RouteModel;
});
