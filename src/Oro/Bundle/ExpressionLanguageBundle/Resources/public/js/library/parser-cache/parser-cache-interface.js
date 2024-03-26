import Interface from 'oroexpressionlanguage/js/library/interface';

/**
 * @interface ParserCacheInterface
 */
class ParserCacheInterface {
    /**
     * Fetches an expression from the cache.
     *
     * @param {string} key  The cache key
     * @return {ParsedExpression|null}
     */
    fetch(key) {}

    /**
     * Saves an expression in the cache.
     *
     * @param {string} key  The cache key
     * @param {ParsedExpression} expression  A ParsedExpression instance to store in the cache
     */
    save(key, expression) {}
}

const parserCacheInterface = new Interface(ParserCacheInterface.prototype);

export default parserCacheInterface;
