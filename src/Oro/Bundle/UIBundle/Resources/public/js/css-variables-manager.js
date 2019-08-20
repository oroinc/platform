define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var cssVars = require('css-vars-ponyfill');
    var module = require('module');
    var config = _.defaults(module.config(), {
        onlyLegacy: false,
        preserveStatic: false,
        updateDOM: false,
        updateURLs: false
    });

    var cssVariablesManager = {
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
            this.getComputedVariables();

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
         * Get hot computed variables
         */
        getComputedVariables: function() {
            var regexp = /(--[\w-]*:)/g;
            var regexpVal = /:\s?[\w\d-(): ]*/g;
            var content = window.getComputedStyle(document.head, ':before').getPropertyValue('content');

            if (content === 'none') {
                this.deferred.resolve(this.cssVariables);
                mediator.trigger('css:breakpoints:fetched', this.cssVariables);
                return;
            }

            content = content.split('|');
            content.forEach(_.bind(function(value, i) {
                var name = value.match(regexp);
                var varVal = value.match(regexpVal);
                if (name && varVal) {
                    this.cssVariables[name[0].slice(0, -1)] = varVal[0].substr(1).trim();
                }

                if (i === content.length - 1) {
                    this.deferred.resolve(this.cssVariables);
                    mediator.trigger('css:breakpoints:fetched', this.cssVariables);
                }
            }, this));
        }
    };

    return cssVariablesManager;
});
