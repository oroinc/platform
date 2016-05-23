define(['jquery', 'underscore', 'chaplin'], function($, _, Chaplin) {
    'use strict';

    /**
     * @export oroui/js/tools
     * @name   oroui.tools
     */
    var tools = {};
    var iOS = /(iPad|iPhone)/.test(navigator.userAgent);

    _.extend(tools, Chaplin.utils);

    _.extend(tools, {
        /** @type {boolean} */
        debug: false,
        /**
         * Pack object to string
         *
         * Object {foo: 'x', 'bar': 'y'} will be converted to string "foo=x&bar=y".
         *
         * @param {Object} object
         * @return {String}
         */
        packToQueryString: function(object) {
            return $.param(object);
        },

        /**
         * Unpack string to object. Reverse from packToQueryString.
         *
         * @param {String} query
         * @return {Object}
         */
        unpackFromQueryString: function(query) {
            var setValue = function(root, path, value) {
                if (path.length > 1) {
                    var dir = path.shift();
                    if (typeof root[dir] === 'undefined') {
                        root[dir] = path[0] === '' ? [] : {};
                    }
                    setValue(root[dir], path, value);
                } else {
                    if (root instanceof Array) {
                        root.push(value);
                    } else {
                        root[path] = value;
                    }
                }
            };
            var nvp = query.split('&');
            var data = {};
            for (var i = 0 ; i < nvp.length ; i++) {
                var pair = nvp[i].split('=');
                var name = this.decodeUriComponent(pair[0]);
                var value = this.decodeUriComponent(pair[1]);

                var path = name.match(/(^[^\[]+)(\[.*\]$)?/);
                var first = path[1];
                if (path[2]) {
                    //case of 'array[level1]' || 'array[level1][level2]'
                    path = path[2].match(/(?=\[(.*)\]$)/)[1].split('][');
                } else {
                    //case of 'name'
                    path = [];
                }
                path.unshift(first);

                setValue(data, path, value);
            }
            return data;
        },

        /**
         * Decode URL encoded component
         *
         * @param {String} string
         * @return {String}
         * @protected
         */
        decodeUriComponent: function(string) {
            var result = string.replace(/\+/g, '%20');
            result = decodeURIComponent(result);
            return result;
        },

        /**
         * Invert object keys.
         *
         * Example of usage:
         *
         * oro.app.invertKeys({foo: 'x', bar: 'y'}, {foo: 'f', bar: 'b'})
         * will return {f: 'x', b: 'y'}
         *
         * @param {Object} object
         * @param {Object} keys
         * @return {Object}
         */
        invertKeys: function(object, keys) {
            var result = _.extend({}, object);
            for (var key in keys) {
                if (!keys.hasOwnProperty(key)) {
                    continue;
                }
                var baseKey = key;
                var mirrorKey = keys[key];

                if (baseKey in result) {
                    result[mirrorKey] = result[baseKey];
                    delete result[baseKey];
                }
            }
            return result;
        },

        /**
         * Loosely compare two values
         *
         * @param {*} value1
         * @param {*} value2
         * @return {Boolean} TRUE if values are equal, otherwise - FALSE
         */
        isEqualsLoosely: function(value1, value2) {
            if (!_.isObject(value1)) {
                if (_.isNumber(value1) || _.isNumber(value2)) {
                    var toNumber = function(v) {
                        if (_.isString(v) && v === '') {
                            return NaN;
                        }
                        return Number(v);
                    };
                    return (toNumber(value1) === toNumber(value2));
                }
                return ((value1 || '') === (value2 || ''));
            } else if (_.isObject(value1)) {
                var valueKeys = _.keys(value1);

                if (_.isObject(value2)) {
                    valueKeys = _.unique(valueKeys.concat(_.keys(value2)));
                    for (var index in valueKeys) {
                        if (!valueKeys.hasOwnProperty(index)) {
                            continue;
                        }
                        var key = valueKeys[index];
                        if (!_.has(value2, key) || !this.isEqualsLoosely(value1[key], value2[key])) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            } else {
                // jshint -W116
                return value1 == value2;
            }
        },

        /**
         * Deep clone a value
         *
         * @param {*} value
         * @return {*}
         */
        deepClone: function(value) {
            return $.extend(true, {}, value);
        },

        /**
         * Are we currently on mobile
         */
        isMobile: function() {
            return _.isMobile();
        },

        /**
         * Are we currently on iOS device
         */
        isIOS: function() {
            return iOS;
        },

        /**
         * Loads dynamic list of modules and execute callback function with passed modules
         *
         * @param {Object.<string, string>|Array.<string>|string} modules
         *  - Object: where keys are formal module names and values are actual
         *  - Array: module names,
         *  - string: single module name
         * @param {function(Object)} callback
         * @param {Object=} context
         */
        loadModules: function(modules, callback, context) {
            var requirements;
            var onLoadHandler;
            if (_.isObject(modules)) {
                // if modules is an object of {formal_name: module_name}
                requirements = _.values(modules);
                onLoadHandler = function() {
                    // maps loaded modules into original object
                    _.each(modules, _.bind(function(value, key) {
                        modules[key] = this[value];
                    }, _.object(requirements, _.toArray(arguments))));
                    callback.call(context || null, modules);
                };
            } else {
                // if modules is an array of module_names or single module_name
                requirements = !_.isArray(modules) ? [modules] : modules;
                onLoadHandler = function() {
                    callback.apply(context || null, arguments);
                };
            }
            // loads requirements and execute onLoadHandler handler
            require(requirements, onLoadHandler);
        },

        /**
         * Loads single module through requireJS and returns promise
         *
         * @param {string} module name
         * @return ($.Promise}
         */
        loadModule: function(module) {
            var deferred = $.Deferred();
            require([module], function(moduleRealization) {
                deferred.resolve(moduleRealization);
            }, function(e) {
                deferred.reject(e);
            });
            return deferred.promise();
        },

        /**
         * Loads single module through requireJS and replaces the property
         *
         * @param {Object} container where to replace property
         * @param {string} property name to replace module ref to concrete realization
         * @return ($.Promise}
         */
        loadModuleAndReplace: function(container, moduleProperty) {
            if (_.isFunction(container[moduleProperty])) {
                var deferred = $.Deferred();
                deferred.resolve(container[moduleProperty]);
                return deferred.promise();
            }
            return this.loadModule(container[moduleProperty]).then(function(realization) {
                container[moduleProperty] = realization;
                return realization;
            });
        },

        /**
         * Check if current page is an error page (404, 503, 504, etc.)
         * @returns {boolean}
         */
        isErrorPage: function() {
            return Boolean($('meta[name=error]').length);
        },

        /**
         * Creates safe regexp expression from string
         *
         * @param {string} str
         * @param {string} flags
         */
        safeRegExp: function(str, flags) {
            var expression;
            str = str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
            expression = new RegExp('(' + str + ')', flags);
            return expression;
        },

        /**
         * Generates Version 4 random UUIDs (https://en.wikipedia.org/wiki/Universally_unique_identifier)
         * @return {string}
         */
        createRandomUUID: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                // jshint -W016
                var r = Math.random() * 16 | 0;
                var v = c === 'x' ? r : (r & 0x3 | 0x8);
                // jshint +W016
                return v.toString(16);
            });
        },

        /**
         * Checks input values and if it isn't an array wraps it
         *
         * @returns {Array}
         */
        ensureArray: function(value) {
            return _.isArray(value) ? value : [value];
        },

        /**
         * Adds css rules to the first style sheet
         *
         * @param {string} selector
         * @param {string} styles
         */
        addCSSRule: function(selector, styles) {
            document.styleSheets[0].insertRule(selector + '{' + styles + '}', 0);
        },

        /**
         * @param {Object} event
         */
        isTargetBlankEvent: function(event) {
            var mouseMiddleButton = 2;
            return this.modifierKeyPressed(event) || event.which === mouseMiddleButton;
        }
    });

    return tools;
});
