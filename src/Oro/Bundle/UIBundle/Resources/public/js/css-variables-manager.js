define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var cssVars = require('https://cdn.jsdelivr.net/npm/css-vars-ponyfill@2');

    var cssVariablesManager = {
        cssVariables: null,

        deferred: $.Deferred(),

        initialize: function() {
            cssVars({
                onlyLegacy: false,
                onComplete: _.bind(function(cssText, styleNodes, cssVariables) {
                    this.cssVariables = cssVariables;

                    mediator.trigger('viewport:css:variables:fetched', cssVariables);

                    this.deferred.resolve(this.cssVariables);
                }, this)
            });

            return this.deferred.promise;
        },

        getVariables: function() {
            return this.cssVariables;
        },

        onReady: function(callback) {
            this.deferred.then(callback);
        }
    };

    return cssVariablesManager;
});
