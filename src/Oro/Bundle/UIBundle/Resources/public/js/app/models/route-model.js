/*jslint nomen:true, eqeq:true*/
/*global define*/
define(function (require) {
    'use strict';

    var RouteModel,
        _ = require('underscore'),
        routing = require('routing'),
        BaseModel = require('./base/model');

    RouteModel = BaseModel.extend({
        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.updateAccepts();
            this.on('change:routeAccepts change:routeName', this.updateAccepts, this);
        },

        /**
         * Updates list of route arguments accepted by this route
         */
        updateAccepts: function () {
            var route, variableTokens, variables;
            route = require('routing').getRoute(this.get('routeName'));
            variableTokens = _.filter(route.tokens, function (tokenPart){
                return tokenPart[0] === 'variable';
            });
            variables = _.map(variableTokens, function (tokenPart) {
                return tokenPart[3];
            });
            variables.push.apply(variables, this.get('routeAccepts'));
            this.accepts = variables;
        },

        /**
         * Returns url generated for route defined by this model
         *
         * @param options {object} additional options for this route
         * @returns {string} route url
         */
        getUrl: function (options) {
            var routeParams = _.extend(this.toJSON(), options);
            return routing.generate(this.get('routeName'), _.pick(routeParams, this.accepts));
        }
    });

    return RouteModel;
});
