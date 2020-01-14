define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const cssVars = require('css-vars-ponyfill');
    let config = require('module-config').default(module.id);
    config = _.defaults({}, config, {
        onlyLegacy: false,
        preserveStatic: false,
        updateDOM: false,
        updateURLs: false
    });

    const cssVariablesManager = {
        /**
         * @property {Object}
         */
        cssVariables: {},

        /**
         * @property {Promise}
         */
        deferred: $.Deferred(),

        /**
         * @inheritDoc
         * @returns {(target?: any) => JQueryPromise<T>}
         */
        initialize: function() {
            this.createHandlers();
            this.getComputedVariables(document.head, this.deferred);

            cssVars(_.extend(config, {
                onComplete: _.bind(function(cssText, styleNodes, cssVariables) {
                    this.cssVariables = _.extend(this.cssVariables, cssVariables);

                    mediator.trigger('css:variables:fetched', this.cssVariables);
                }, this)
            }));

            return this.deferred.promise;
        },

        /**
         * Ger array of CSS variables
         * @returns {cssVariablesManager.cssVariables|{}}
         */
        getVariables: function() {
            return this.cssVariables;
        },

        /**
         * Ready handle
         * @param callback
         */
        onReady: function(callback) {
            this.deferred.then(callback);
        },

        /**
         * Create mediator methods
         */
        createHandlers: function() {
            mediator.setHandler('fetch:head:computedVars', this.getHeadBreakpoints, this);
        },

        /**
         * Get hot computed variables
         */
        getComputedVariables: function(context, defer) {
            if (!context) {
                context = document.head;
            }

            const regexp = /(--[\w-]*:)/g;
            const regexpVal = /:\s?[\w\d-(): ]*/g;
            let content = window.getComputedStyle(context, ':before').getPropertyValue('content');
            const breakpoint = {};

            if (content === 'none') {
                mediator.trigger('css:breakpoints:fetched', breakpoint);
                if (defer) {
                    defer.resolve(breakpoint);
                }
                return;
            }

            content = content.split('|');
            content.forEach(_.bind(function(value, i) {
                const name = value.match(regexp);
                const varVal = value.match(regexpVal);
                if (name && varVal) {
                    breakpoint[name[0].slice(0, -1)] = varVal[0].substr(1).trim();
                }

                if (i === content.length - 1) {
                    if (defer) {
                        defer.resolve(breakpoint);
                    }
                    mediator.trigger('css:breakpoints:fetched', breakpoint);
                }
            }, this));

            return breakpoint;
        },

        /**
         * Callback mediator handler
         * @param context
         * @returns {*}
         */
        getHeadBreakpoints: function(context) {
            return this.getComputedVariables(context);
        }
    };

    return cssVariablesManager;
});
