/*jslint nomen:true, eqeq:true*/
/*global define*/
define(function (require) {
    'use strict';

    var Route,
        routing = require('routing'),
        BaseModel = require('./base/model');

    Route = BaseModel.extend({
        getUrl: function (options) {
            var routeParams = _.extend(this.toJSON(), options);
            return routing.generate(this.get('routeName'), routeParams);
        }
    });

    return Route;
});
