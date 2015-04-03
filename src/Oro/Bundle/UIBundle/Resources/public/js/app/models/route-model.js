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
    var RouteModel,
        _ = require('underscore'),
        routing = require('routing'),
        BaseModel = require('./base/model');

    RouteModel = BaseModel.extend(/** @exports RouteModel.prototype */{
        /**
         * @inheritDoc
         * @member {Object}
         */
        defaults: function () {
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
         * Return list of parameter names accepted by this route.
         * Includes both query and route parameters,
         *
         * E.g. for route `api/rest/latest/<relationClass>/<relationId/comments?page=<page>&limit=<limit>`
         * this function will return `['relationClass', 'relationId', 'page', 'limit']`
         *
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
            routeParameters.push.apply(routeParameters, this.get('routeQueryParameterNames'));
            return _.uniq(routeParameters);
        },

        /**
         * Returns url defined by this model
         *
         * @param parameters {Object=} parameters to override
         * @returns {string} route url
         */
        getUrl: function (parameters) {
            var routeParameters = _.extend(this.toJSON(), parameters),
                acceptableParameters = this.getAcceptableParameters();
            return routing.generate(this.get('routeName'), _.pick(routeParameters, acceptableParameters));
        }
    });

    return RouteModel;
});
