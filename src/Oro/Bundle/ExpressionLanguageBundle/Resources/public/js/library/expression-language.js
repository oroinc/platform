import expressionFunctionProviderInterface
    from 'oroexpressionlanguage/js/library/expression-function-provider-interface';
import ArrayParserCache from 'oroexpressionlanguage/js/library/parser-cache/array-parser-cache';
import parserCacheInterface from 'oroexpressionlanguage/js/library/parser-cache/parser-cache-interface';
import ParsedExpression from 'oroexpressionlanguage/js/library/parsed-expression';
import Lexer from 'oroexpressionlanguage/js/library/lexer';
import Parser from 'oroexpressionlanguage/js/library/parser';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

class ExpressionLanguage {
    /**
     * @param {ParserCacheInterface} [cache]
     * @param {Array.<ExpressionFunctionProviderInterface>} [providers]
     */
    constructor(cache, providers = []) {
        if (cache) {
            parserCacheInterface.expectToBeImplementedBy(cache);
            this.cache = cache;
        } else {
            this.cache = new ArrayParserCache();
        }
        this.lexer = null;
        this.parser = null;
        this.compiler = null;
        this.functions = {};
        this.registerFunctions();
        providers.forEach(this.registerProvider.bind(this));
    }

    /**
     * Compiles an expression source code.
     *
     * @param {Expression|string} expression  The expression to compile
     * @param {Array|Object} [names]  An array or hash of valid names
     *
     * @return {string}  The compiled PHP source code
     */
    compile(expression, names = []) {
        return this.getCompiler().compile(this.parse(expression, names).getNodes()).getSource();
    }

    /**
     * Evaluate an expression.
     *
     * @param {Expression|string} expression  The expression to evaluate
     * @param {Object} [values]  An hash of values
     *
     * @return {string}  The result of the evaluation of the expression
     */
    evaluate(expression, values = {}) {
        return this.parse(expression, Object.keys(values)).getNodes().evaluate(this.functions, values);
    }

    /**
     * Parses an expression.
     *
     * @param {Expression|string} expression  The expression to parse
     * @param {Array|Object} names  An array or hash of valid names
     *
     * @return {ParsedExpression}  A ParsedExpression instance
     */
    parse(expression, names) {
        if (expression instanceof ParsedExpression) {
            return expression;
        }

        const keys = Object.keys(names);

        keys.sort((a, b) => {
            if (names[a] === names[b]) {
                return 0;
            }
            return names[a] > names[b] ? 1 : -1;
        });

        const cacheKeyItems = keys.map(key => {
            const keyIsInt = key >= 0 && key % 1 === 0;
            return keyIsInt ? names[key] : key + ':' + names[key];
        });

        const key = expression + '//' + cacheKeyItems.join('|');

        let parsedExpression = this.cache.fetch(key);

        if (null === parsedExpression) {
            const nodes = this.getParser().parse(this.getLexer().tokenize(String(expression)), names);
            parsedExpression = new ParsedExpression(String(expression), nodes);
            this.cache.save(key, parsedExpression);
        }

        return parsedExpression;
    }

    /**
     * Registers a function.
     *
     * @param {string} name  The function name
     * @param {Function} compiler  A callback to compile the function
     * @param {Function} evaluator  A callback to evaluate the function
     *
     * @throws {Error} when registering a function after calling evaluate(), compile() or parse()
     *
     * @see ExpressionFunction
     */
    register(name, compiler, evaluator) {
        if (null !== this.parser) {
            throw new Error(
                'Registering functions after calling evaluate(), compile() or parse() is not supported.');
        }

        this.functions[name] = {
            compiler: compiler,
            evaluator: evaluator
        };
    }

    addFunction(func) {
        this.register(func.getName(), func.getCompiler(), func.getEvaluator());
    }

    /**
     * @param {ExpressionFunctionProviderInterface} provider
     */
    registerProvider(provider) {
        expressionFunctionProviderInterface.expectToBeImplementedBy(provider);
        provider.getFunctions().forEach(this.addFunction.bind(this));
    }

    registerFunctions() {
        // Since JS and PHP don't share any constants between each other you can't implement constant function
        // as it did in Symphony expression language library
    }

    getLexer() {
        if (null === this.lexer) {
            this.lexer = new Lexer();
        }

        return this.lexer;
    }

    getParser() {
        if (null === this.parser) {
            this.parser = new Parser(this.functions);
        }

        return this.parser;
    }

    getCompiler() {
        if (null === this.compiler) {
            this.compiler = new Compiler(this.functions);
        }

        return this.compiler.reset();
    }
}

export default ExpressionLanguage;
