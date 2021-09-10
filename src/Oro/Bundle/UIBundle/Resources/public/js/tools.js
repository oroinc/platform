define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const loadModules = require('oroui/js/app/services/load-modules');
    const tools = {};
    const iOS = /(iPad|iPhone)/.test(navigator.userAgent);
    const iPadOS = !!(navigator.userAgent.match(/Mac/) && navigator.maxTouchPoints && navigator.maxTouchPoints > 2);

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
         * Pack object to string with query parameters sorted
         *
         * Object {foo: 'x', 'bar': 'y'} will be converted to string "foo=x&bar=y".
         *
         * @param {Object} object
         * @return {String}
         */
        packToQuerySortedString: function(object) {
            const self = this;
            const result = [];
            const add = function(key, value) {
                result[result.length] = encodeURIComponent(key) + '=' +
                    encodeURIComponent(value === null ? '' : value);
            };

            const buildParams = function(pref, obj) {
                if (self.isArrayLikeObject(obj)) {
                    obj = _.toArray(obj);
                }

                if (_.isArray(obj)) {
                    obj.sort();
                    _.each(obj, function(value, key) {
                        buildParams(pref + '[' + (typeof value === 'object' ? key : '') + ']', value);
                    });
                } else if (typeof obj === 'object') {
                    const keys = _.keys(obj);
                    keys.sort();
                    for (let i = 0; i < keys.length; i++) {
                        const name = keys[i];
                        buildParams(pref + '[' + name + ']', obj[name]);
                    }
                } else {
                    add(pref, obj);
                }
            };

            const keys = _.keys(object);
            keys.sort();
            for (let i = 0; i < keys.length; i++) {
                const prefix = keys[i];
                buildParams(prefix, object[prefix]);
            }
            return result.join('&');
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
            const setValue = function(root, path, value) {
                if (path.length > 1) {
                    const dir = path.shift();
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
            const nvp = query.split('&');
            const data = {};
            for (let i = 0; i < nvp.length; i++) {
                const pair = nvp[i].split('=');
                if (pair.length < 2) {
                    continue;
                }
                const name = this.decodeUriComponent(pair[0]);
                const value = this.decodeUriComponent(pair[1]);

                let path = name.match(/(^[^\[]+)(\[.*\]$)?/);
                const first = path[1];
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
            let result = string.replace(/\+/g, '%20');
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
            const result = _.extend({}, object);
            for (const key in keys) {
                if (!keys.hasOwnProperty(key)) {
                    continue;
                }
                const baseKey = key;
                const mirrorKey = keys[key];

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
                    const toNumber = function(v) {
                        if (_.isString(v) && v === '') {
                            return NaN;
                        }
                        return Number(v);
                    };
                    return (toNumber(value1) === toNumber(value2));
                }
                return ((value1 || '') === (value2 || ''));
            } else if (_.isObject(value1)) {
                let valueKeys = _.keys(value1);

                if (_.isObject(value2)) {
                    valueKeys = _.unique(valueKeys.concat(_.keys(value2)));
                    for (const index in valueKeys) {
                        if (!valueKeys.hasOwnProperty(index)) {
                            continue;
                        }
                        const key = valueKeys[index];
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
            return 'ontouchstart' in window ||
                window.DocumentTouch && document instanceof window.DocumentTouch ||
                navigator.maxTouchPoints > 0 ||
                window.navigator.msMaxTouchPoints > 0;
        },

        /**
         * Are we currently on iOS device
         */
        isIOS: function() {
            return iOS || iPadOS;
        },

        /**
         * Loads dynamic list of modules and execute callback function with passed modules
         *
         * @deprecated use 'oroui/js/app/services/load-modules'
         * @param {Object.<string, string>|Array.<string>|string} modules
         *  - Object: where keys are formal module names and values are actual
         *  - Array: module names,
         *  - string: single module name
         * @param {function(Object)=} callback
         * @param {Object=} context
         * @return {Promise}
         */
        loadModules: loadModules,

        /**
         * Loads single module and returns promise
         *
         * @deprecated use 'oroui/js/app/services/load-modules'
         * @param {string} module name
         * @return {Promise}
         */
        loadModule: loadModules,

        /**
         * Loads single module and replaces the property
         *
         * @param {Object} container where to replace property
         * @param {string} moduleProperty name to replace module ref to concrete realization
         * @return {JQueryPromise}
         */
        loadModuleAndReplace: function(container, moduleProperty) {
            if (typeof container[moduleProperty] !== 'string') {
                const deferred = $.Deferred();
                deferred.resolve(container[moduleProperty]);
                return deferred.promise();
            }
            return loadModules(container[moduleProperty]).then(function(realization) {
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
            str = str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
            const expression = new RegExp('(' + str + ')', flags);
            return expression;
        },

        /**
         * Generates Version 4 random UUIDs (https://en.wikipedia.org/wiki/Universally_unique_identifier)
         * @return {string}
         */
        createRandomUUID: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
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
         * Check is this object can be represented as an array
         *
         * @param {Object} object
         * @returns {boolean}
         */
        isArrayLikeObject: function(object) {
            if (typeof object !== 'object') {
                return false;
            }
            const keys = _.keys(object);
            for (let i = 0; i < keys.length; i++) {
                const key = keys[i];
                if (String(key) !== String(i)) {
                    return false;
                }
            }
            return true;
        },

        /**
         * Checks if passed value represents a number (e.g. 3.5 and '3.5' are numeric)
         *
         * @param {*} value
         * @return {boolean}
         */
        isNumeric: function(value) {
            return !isNaN(parseFloat(value)) && isFinite(value);
        },

        /**
         * Adds css rules to the first style sheet
         *
         * @param {string} selector
         * @param {string} styles
         */
        addCSSRule: function(selector, styles) {
            const css = `${selector} { ${styles} }`;
            const ID = '__runtime-styles';
            const style = document.getElementById(ID) || (() => {
                const head = document.head || document.getElementsByTagName('head')[0];
                const style = document.createElement('style');
                style.type = 'text/css';
                style.id = ID;
                head.appendChild(style);
                return style;
            })();

            style.appendChild(document.createTextNode(css));
        },

        /**
         * @param {Object} event
         */
        isTargetBlankEvent: function(event) {
            const mouseMiddleButton = 2;
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
            const paths = [];
            let tagName;
            if (element && element.id) {
                tagName = element.nodeName.toLowerCase();
                return '//' + tagName + '[@id="' + element.id + '"]';
            } else {
                // Use nodeName (instead of localName) so namespace prefix is included (if any).
                for (; element && element.nodeType === 1; element = element.parentNode) {
                    let index = 0;
                    tagName = element.nodeName.toLowerCase();
                    // EXTRA TEST FOR ELEMENT.ID
                    if (element && element.id) {
                        paths.splice(0, 0, '/' + tagName + '[@id="' + element.id + '"]');
                        break;
                    }

                    for (let sibling = element.previousSibling; sibling; sibling = sibling.previousSibling) {
                        // Ignore document type declaration.
                        if (sibling.nodeType === Node.DOCUMENT_TYPE_NODE) {
                            continue;
                        }
                        if (sibling.nodeName === element.nodeName) {
                            ++index;
                        }
                    }

                    const pathIndex = '[' + (index + 1) + ']';
                    const classAttr = element.className ? '[@class="' + element.className + '"]' : '';
                    paths.splice(0, 0, tagName + pathIndex + classAttr);
                }

                return paths.length ? '/' + paths.join('/') : null;
            }
        },

        /**
         * Gets unique CSS selector for DOM element.
         *
         * @param {HTMLElement} element
         * @param {boolean} [clearPath=true]
         * @returns {string}
         */
        getElementCSSPath: function(element, clearPath = true) {
            const buildPath = (el, path = []) => {
                if (el && el.nodeType === Node.ELEMENT_NODE) {
                    let part = el.nodeName.toLowerCase();

                    if (el.className) {
                        part += `.${el.className.trim().split(' ')[0]}`;
                    }

                    if (el.nodeName !== 'HTML') {
                        part += `:nth-child(${$(el).index() + 1})`;
                    }

                    return buildPath(el.parentNode, path.concat([part]));
                } else {
                    const preparedPath = path.reverse().join(' > ');

                    if (clearPath) {
                        const regExp = /(\.js-focus-visible|\.focus-visible|\.hide|\.show)/;

                        return preparedPath.replace(new RegExp(regExp, 'g'), '');
                    }

                    return preparedPath;
                }
            };

            return buildPath(element);
        },

        getPrototypeChain: function(object) {
            const chain = [];
            while (object = Object.getPrototypeOf(object)) {
                chain.unshift(object);
            }
            return chain;
        },

        getAllPropertyVersions: function(object, name) {
            const versions = [];
            const prototypeChain = tools.getPrototypeChain(object);
            prototypeChain.forEach(prototype => {
                if (prototype.hasOwnProperty(name)) {
                    versions.push(prototype[name]);
                }
            });
            return versions;
        }
    });

    return tools;
});
