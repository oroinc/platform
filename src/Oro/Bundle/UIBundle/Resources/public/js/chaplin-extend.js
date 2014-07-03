/*jslint browser:true, nomen:true, white:true, eqeq:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'chaplin'
], function ($, _, Chaplin) {
    'use strict';

    var utils, location;

    utils = Chaplin.utils;
    location = window.location;

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

    Chaplin.Layout.prototype.el = 'document';

    /**
     * Fixes issues
     *  - empty hashes (like '#')
     *  - routing full url (containing protocol and host)
     * @override
     */
    Chaplin.Layout.prototype.openLink = _.wrap(Chaplin.Layout.prototype.openLink, function(func, event) {
        var el, href;
        el = event.currentTarget;

        if (event.isDefaultPrevented()) {
            return;
        }

        if (el.nodeName === 'A') {
            href = el.getAttribute('href');
            // prevent click by empty hashes
            if (el.getAttribute('href') === '#') {
                event.preventDefault();
                return;
            }
            // fixes issue of routing full url
            if (href.indexOf(':\/\/') !== -1 && el.host === location.host) {
                el.setAttribute('href', el.pathname + el.search + el.hash);
            }
        }

        func.call(this, event);
    });

    return Chaplin;
});
