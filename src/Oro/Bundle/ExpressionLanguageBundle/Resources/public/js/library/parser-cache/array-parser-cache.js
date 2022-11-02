import parserCacheInterface from './parser-cache-interface';

/**
 * @implements {ParserCacheInterface}
 */
class ArrayParserCache {
    constructor() {
        this.cache = {};
    }

    /**
     * Fetches an expression from the cache.
     *
     * @param {string} key  The cache key
     * @return {ParsedExpression|null}
     */
    fetch(key) {
        return this.cache.hasOwnProperty(key) ? this.cache[key] : null;
    }

    /**
     * Saves an expression in the cache.
     *
     * @param {string} key  The cache key
     * @param {ParsedExpression} expression  A ParsedExpression instance to store in the cache
     */
    save(key, expression) {
        this.cache[key] = expression;
    }
}

parserCacheInterface.expectToBeImplementedBy(ArrayParserCache.prototype);

export default ArrayParserCache;
