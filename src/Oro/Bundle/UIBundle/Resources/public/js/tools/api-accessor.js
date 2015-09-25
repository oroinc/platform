/** @lends ApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Abstraction of api access point
     *
     * Useful for parsing configuration that needs to be transformed into server request
     *
     * @class
     * @augment StdClass
     * @exports ApiAccessor
     */
    var ApiAccessor;

    var _ = require('underscore');
    var $ = require('jquery');
    var BaseClass = require('../base-class');
    var RouteModel = require('../app/models/route-model');

    ApiAccessor = BaseClass.extend(/** @exports ApiAccessor.prototype */{
        DEFAULT_HEADERS: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },

        DEFAULT_HTTP_METHOD: 'POST',

        initialize: function(options) {
            if (!options) {
                options = {};
            }
            this.httpMethod = options.http_method || this.DEFAULT_HTTP_METHOD;
            this.headers = _.extend({}, this.DEFAULT_HEADERS, options.headers || {});
            // init route model
            if (!options.route) {
                throw Error('"route" is a required option');
            }
            this.route = new RouteModel(_.extend({}, options.default_route_parameters, {
                routeName: options.route,
                routeQueryParameterNames: options.query_parameter_names
            }));
        },

        getHeaders: function(headers) {
            return _.extend({}, this.headers, headers || {});
        },

        getUrl: function(urlParameters) {
            return this.route.getUrl(urlParameters);
        },

        send: function(urlParameters, body, headers) {
            return $.ajax({
                headers: this.getHeaders(headers),
                type: this.httpMethod,
                url: this.getUrl(urlParameters),
                data: JSON.stringify(body)
            });
        }
    });

    return ApiAccessor;
});
