/** @lends ApiAccessor */
define(function(require) {
    'use strict';

    /**
     * Abstraction of api access point. This class is designed to create from server configuration.
     *
     * Options:
     * Name  | Description
     * ------|------------
     * route | Route name
     * http_method | Http method to access this route. (GET|POST|PUT|PATCH...)
     * form_name | Wraps request body into form_name, so request will look like ```
     *             {<form_name>:{<field_name>: <new_value>}}
     *             ```
     * headers | Allows to provide additional http headers
     * default_route_parameters | provides default parameters values for route creation,
     *                            this defaults will be merged with row model data to get url
     * query_parameter_names | array of parameter names to put into query string
     *                         (e.g. ?<parameter-name>=<value>&<parameter-name>=<value>).
     *                         (The reason is that FOSRestBundle doesn’t provides them for client usage, \
     *                         so it is required to specify list of available query parameters)
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

        DEFAULT_HTTP_METHOD: 'GET',

        formName: void 0,

        /**
         * @constructor
         * @param options
         */
        initialize: function(options) {
            if (!options) {
                options = {};
            }
            this.initialOptions = options;
            this.httpMethod = options.http_method || this.DEFAULT_HTTP_METHOD;
            this.headers = _.extend({}, this.DEFAULT_HEADERS, options.headers || {});
            this.formName = options.form_name;
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

        formatBody: function(body) {
            var formattedBody;
            if (this.formName) {
                formattedBody = {};
                formattedBody[this.formName] = body;
            } else {
                formattedBody = body;
            }
            return formattedBody;
        },

        send: function(urlParameters, body, headers) {
            return $.ajax({
                headers: this.getHeaders(headers),
                type: this.httpMethod,
                url: this.getUrl(urlParameters),
                data: JSON.stringify(this.formatBody(body))
            });
        }
    });

    return ApiAccessor;
});
