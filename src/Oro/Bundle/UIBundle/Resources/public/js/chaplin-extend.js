/*jslint nomen:true, white:true, eqeq:true*/
/*global define*/
define(['underscore', 'chaplin'], function (_, Chaplin) {
    'use strict';

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

    return Chaplin;
});
