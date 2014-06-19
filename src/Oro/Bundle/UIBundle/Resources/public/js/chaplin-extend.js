/*jslint nomen:true, white:true, eqeq:true*/
/*global define*/
define(['underscore', 'chaplin'], function (_, Chaplin) {
    'use strict';

    var utils;

    utils = Chaplin.utils;

    /**
     * Fixes issue where path '/' was converted to boolean false value
     * @override
     */
    Chaplin.Router.prototype.route = function(pathDesc, params, options) {
        var handler, path;
        if (typeof pathDesc === 'object') {
            path = pathDesc.url;
            if (!params && pathDesc.params) {
                params = pathDesc.params;
            }
        }
        params = params ? _.isArray(params) ? params.slice() : _.extend({}, params) : {};
        if (path != null) {
            path = path.replace(this.removeRoot, '');
            handler = this.findHandler(function(handler) {
                return handler.route.test(path);
            });
            options = params;
            params = null;
        } else {
            options = options ? _.extend({}, options) : {};
            handler = this.findHandler(function(handler) {
                if (handler.route.matches(pathDesc)) {
                    params = handler.route.normalizeParams(params);
                    if (params) {
                        return true;
                    }
                }
                return false;
            });
        }
        if (handler) {
            _.defaults(options, {
                changeURL: true
            });
            handler.callback(path != null ? path : params, options);
            return true;
        } else {
            throw new Error('Router#route: request was not routed');
        }
    };

    /**
     * Fixes issue when correspondent over options regions are not taken into account
     * @override
     */
    Chaplin.Layout.prototype.registerGlobalRegions = function(instance) {
        var name, selector, version, _i, _len, _ref;
        _ref = utils.getAllPropertyVersions(instance, 'regions');
        if (instance.hasOwnProperty('regions')) {
            _ref.push(instance.regions);
        }
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            version = _ref[_i];
            for (name in version) {
                selector = version[name];
                this.registerGlobalRegion(instance, name, selector);
            }
        }
    };

    return Chaplin;
});
