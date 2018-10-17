define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = {};
    var iOS = /(iPad|iPhone)/.test(navigator.userAgent);
    var edge = /(Edge\/)/.test(navigator.userAgent);

    /**
     * @export oroui/js/tools
     * @name   oroui.tools
     */
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
            if (query.charAt(0) === '?') {
                query = query.slice(1);
            }
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
            for (var i = 0; i < nvp.length; i++) {
                var pair = nvp[i].split('=');
                if (pair.length < 2) {
                    continue;
                }
                var name = this.decodeUriComponent(pair[0]);
                var value = this.decodeUriComponent(pair[1]);

                var path = name.match(/(^[^\[]+)(\[.*\]$)?/);
                var first = path[1];
                if (path[2]) {
                    // case of 'array[level1]' || 'array[level1][level2]'
                    path = path[2].match(/(?=\[(.*)\]$)/)[1].split('][');
                } else {
                    // case of 'name'
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
                return value1 == value2; // eslint-disable-line eqeqeq
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
         * Are we currently on desktop
         */
        isDesktop: function() {
            return _.isDesktop();
        },

        /**
         * Are we have touch screen
         */
        isTouchDevice: function() {
            return ('ontouchstart' in window && window.ontouchstart) ||
                ('DocumentTouch' in window && document instanceof window.DocumentTouch);
        },

        /**
         * Are we currently on iOS device
         */
        isIOS: function() {
            return iOS;
        },

        /**
         * Are we currently on EDGE browser
         */
        isEDGE: function() {
            return edge;
        },

        /**
         * Loads dynamic list of modules and execute callback function with passed modules
         *
         * @param {Object.<string, string>|Array.<string>|string} modules
         *  - Object: where keys are formal module names and values are actual
         *  - Array: module names,
         *  - string: single module name
         * @param {function(Object)=} callback
         * @param {Object=} context
         * @return {JQueryPromise}
         */
        loadModules: function(modules, callback, context) {
            var requirements;
            var processModules;

            if (_.isObject(modules) && !_.isArray(modules)) {
                // if modules is an object of {formal_name: module_name}
                requirements = _.values(modules);
                processModules = function(loadedModules) {
                    // maps loaded modules into original object
                    _.each(modules, _.partial(function(map, value, key) {
                        modules[key] = map[value];
                    }, _.object(requirements, loadedModules)));
                    return [modules];
                };
            } else {
                // if modules is an array of module_names or single module_name
                requirements = !_.isArray(modules) ? [modules] : modules;
                processModules = function(loadedModules) {
                    return loadedModules;
                };
            }

            var deferred = $.Deferred();
            require(requirements, _.partial(function(processor) {
                var modules = processor(_.rest(arguments));
                if (callback) {
                    callback.apply(context || null, modules);
                }
                deferred.resolve.apply(deferred, modules);
            }, processModules), function(e) {
                deferred.reject(e);
            });
            return deferred.promise();
        },

        /**
         * Loads single module through requireJS and returns promise
         *
         * @deprecated
         * @param {string} module name
         * @return {JQueryPromise}
         */
        loadModule: function(module) {
            return tools.loadModules(module);
        },

        /**
         * Loads single module through requireJS and replaces the property
         *
         * @param {Object} container where to replace property
         * @param {string} moduleProperty name to replace module ref to concrete realization
         * @return {JQueryPromise}
         */
        loadModuleAndReplace: function(container, moduleProperty) {
            if (_.isFunction(container[moduleProperty])) {
                var deferred = $.Deferred();
                deferred.resolve(container[moduleProperty]);
                return deferred.promise();
            }
            return this.loadModules(container[moduleProperty]).then(function(realization) {
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
                var r = Math.random() * 16 | 0;
                var v = c === 'x' ? r : (r & 0x3 | 0x8);
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
            var styleSheet = document.styleSheets[0];
            styleSheet.insertRule(selector + '{' + styles + '}', styleSheet.cssRules.length);
        },

        /**
         * @param {Object} event
         */
        isTargetBlankEvent: function(event) {
            var mouseMiddleButton = 2;
            return event.shiftKey || event.altKey || event.ctrlKey || event.metaKey ||
                event.which === mouseMiddleButton;
        },

        /**
         * Gets an XPath for an element which describes its hierarchical location.
         *
         * @param {HTMLElement} element
         * @returns {string|null}
         */
        getElementXPath: function(element) {
            var paths = [];
            var tagName = element.nodeName.toLowerCase();
            if (element && element.id) {
                return '//' + tagName + '[@id="' + element.id + '"]';
            } else {
                // Use nodeName (instead of localName) so namespace prefix is included (if any).
                for (; element && element.nodeType === 1; element = element.parentNode) {
                    var index = 0;
                    tagName = element.nodeName.toLowerCase();
                    // EXTRA TEST FOR ELEMENT.ID
                    if (element && element.id) {
                        paths.splice(0, 0, '/' + tagName + '[@id="' + element.id + '"]');
                        break;
                    }

                    for (var sibling = element.previousSibling; sibling; sibling = sibling.previousSibling) {
                        // Ignore document type declaration.
                        if (sibling.nodeType === Node.DOCUMENT_TYPE_NODE) {
                            continue;
                        }
                        if (sibling.nodeName === element.nodeName) {
                            ++index;
                        }
                    }

                    var pathIndex = '[' + (index + 1) + ']';
                    var classAttr = element.className ? '[@class="' + element.className + '"]' : '';
                    paths.splice(0, 0, tagName + pathIndex + classAttr);
                }

                return paths.length ? '/' + paths.join('/') : null;
            }
        }
    });

    return tools;
});
