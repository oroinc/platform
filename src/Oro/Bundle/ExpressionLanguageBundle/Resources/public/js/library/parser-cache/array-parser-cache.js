define(function(require) {
    'use strict';

    var ParserCacheInterface = require('oroexpressionlanguage/js/library/parser-cache/parser-cache-interface');

    function ArrayParserCache() {
        this.cache = {};
    }

    ArrayParserCache.prototype = {
        constructor: ArrayParserCache,

        /**
         * Fetches an expression from the cache.
         *
         * @param {string} key  The cache key
         * @return {ParsedExpression|null}
         */
        fetch: function(key) {
            return this.cache.hasOwnProperty(key) ? this.cache[key] : null;
        },

        /**
         * Saves an expression in the cache.
         *
         * @param {string} key  The cache key
         * @param {ParsedExpression} expression  A ParsedExpression instance to store in the cache
         */
        save: function(key, expression) {
            this.cache[key] = expression;
        }
    };

    ParserCacheInterface.expectToBeImplementedBy(ArrayParserCache.prototype);

    return ArrayParserCache;
});
