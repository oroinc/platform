/*jslint nomen:true, eqeq:true*/
/*global define*/
/** @lends RouteModel */
define(function (require) {
    'use strict';

    /**
     * Abstraction of route
     *
     * Basic usage:
     * ```javascript
     * var route = new RouteModel({
     *     // route specification
     *     routeName: 'oro_api_comment_get_items',
     *     routeQueryParameters: ['page', 'limit'],
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
     * @exports RouteModel
     */
    var RouteModel,
        _ = require('underscore'),
        routing = require('routing'),
        BaseModel = require('./base/model');

    RouteModel = BaseModel.extend(/** @exports RouteModel.prototype */{
        defaults: function () {
            return {
                /**
                 * Name of the route
                 * @type {string}
                 */
                routeName: null,

                /**
                 * List of acceptable query parameters for this route
                 * @type {Array.<string>}
                 */
                routeQueryParameters: []
            };
        },

        /**
         * Return list of parameter names accepted by this route
         * @protected
         * @returns {Array.<string>}
         */
        getAcceptableParameters: function () {
            var route, variableTokens, routeParameters;
            if (!this.get('routeName')) {
                throw new Error('routeName must be specified');
            }
            route = routing.getRoute(this.get('routeName'));
            variableTokens = _.filter(route.tokens, function (tokenPart){
                return tokenPart[0] === 'variable';
            });
            routeParameters = _.map(variableTokens, function (tokenPart) {
                return tokenPart[3];
            });
            routeParameters.push.apply(routeParameters, this.get('routeQueryParameters'));
            return routeParameters;
        },

        /**
         * Returns url defined by this model
         *
         * @param options {Object=} parameters to override
         * @returns {string} route url
         */
        getUrl: function (options) {
            var routeParameters = _.extend(this.toJSON(), options),
                acceptableParameters = this.getAcceptableParameters();
            return routing.generate(this.get('routeName'), _.pick(routeParameters, acceptableParameters));
        }
    });

    return RouteModel;
});
