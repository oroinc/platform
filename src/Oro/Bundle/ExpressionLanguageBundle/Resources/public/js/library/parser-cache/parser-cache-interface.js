define(function(require) {
    'use strict';

    var Interface = require('oroexpressionlanguage/js/library/interface');

    var ParserCacheInterface = new Interface({
        /**
         * Fetches an expression from the cache.
         *
         * @param {string} key  The cache key
         * @return {ParsedExpression|null}
         */
        fetch: function(key) {},

        /**
         * Saves an expression in the cache.
         *
         * @param {string} key  The cache key
         * @param {ParsedExpression} expression  A ParsedExpression instance to store in the cache
         */
        save: function(key, expression) {}
    });

    return ParserCacheInterface;
});
