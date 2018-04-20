define(function(require) {
    'use strict';

    var _ = require('underscore');
    var tools = require('oroui/js/tools');

    var FilterTranslatorsManager = {
        /**
         */
        fromExpression: {},

        /**
         */
        toExpression: {},

        /**
         * @param modules
         */
        loadTranslatorsFromExpression: function(modules) {
            this._loadTranslators(modules, this.fromExpression);
        },

        /**
         * @param modules
         */
        loadTranslatorsToExpression: function(modules) {
            this._loadTranslators(modules, this.toExpression);
        },

        /**
         * @param {Array} modules
         * @param {Object} space
         * @private
         */
        _loadTranslators: function(modules, space) {
            tools.loadModules(modules, function() {
                _.each(arguments, function(module) {
                    this.addTranslator(module, space);
                }, this);
            }, this);
        },

        /**
         * @param {Constructor} Translator
         * @param {Object} space
         */
        addTranslator: function(Translator, space) {
            var translator = new Translator();
            var type = translator.filterType;

            if (type) {
                space[type] = translator;
            }
        },

        /**
         * @returns {*}
         */
        getTranslatorsFromExpression: function() {
            return _.clone(this.fromExpression);
        },

        /**
         * @returns {*}
         */
        getTranslatorsToExpression: function() {
            return _.clone(this.toExpression);
        }
    };

    return FilterTranslatorsManager;
});
