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
        defaults: {
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
        },

        /**
         * List of all parameters accepted by route, includes "path" and "query" parameter names
         *
         * @type {Array.<string>}
         * @protected
         */
        _routeParameters: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this._updateRouteParameters();
            this.on('change:routeName change:routeQueryParameters', this._updateRouteParameters, this);
        },

        /**
         * Updates list of route arguments accepted by this route
         * @protected
         */
        _updateRouteParameters: function () {
            var route, variableTokens, routeParameters;
            route = routing.getRoute(this.get('routeName'));
            variableTokens = _.filter(route.tokens, function (tokenPart){
                return tokenPart[0] === 'variable';
            });
            routeParameters = _.map(variableTokens, function (tokenPart) {
                return tokenPart[3];
            });
            routeParameters.push.apply(routeParameters, this.get('routeQueryParameters'));
            this._routeParameters = routeParameters;
        },

        /**
         * Returns url defined by this model
         *
         * @param options {object} parameters to override
         * @returns {string} route url
         */
        getUrl: function (options) {
            var routeParams = _.extend(this.toJSON(), options);
            return routing.generate(this.get('routeName'), _.pick(routeParams, this._routeParameters));
        }
    });

    return RouteModel;
});
