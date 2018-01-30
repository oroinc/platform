define(function(require) {
    'use strict';

    var ExpressionFunctionProviderInterface =
        require('oroexpressionlanguage/js/library/expression-function-provider-interface');
    var ArrayParserCache = require('oroexpressionlanguage/js/library/parser-cache/array-parser-cache');
    var ParserCacheInterface = require('oroexpressionlanguage/js/library/parser-cache/parser-cache-interface');
    var ParsedExpression = require('oroexpressionlanguage/js/library/parsed-expression');
    var Lexer = require('oroexpressionlanguage/js/library/lexer');
    var Parser = require('oroexpressionlanguage/js/library/parser');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    /**
     * @param {ParserCacheInterface} [cache]
     * @param {Array.<ExpressionFunctionProviderInterface>} [providers]
     */
    function ExpressionLanguage(cache, providers) {
        providers = providers || [];
        if (cache) {
            ParserCacheInterface.expectToBeImplementedBy(cache);
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

    ExpressionLanguage.prototype = {
        constructor: ExpressionLanguage,

        /**
         * Compiles an expression source code.
         *
         * @param {Expression|string} expression  The expression to compile
         * @param {Array|Object} [names]  An array or hash of valid names
         *
         * @return {string}  The compiled PHP source code
         */
        compile: function(expression, names) {
            names = names || [];
            return this.getCompiler().compile(this.parse(expression, names).getNodes()).getSource();
        },

        /**
         * Evaluate an expression.
         *
         * @param {Expression|string} expression  The expression to evaluate
         * @param {Object} [values]  An hash of values
         *
         * @return {string}  The result of the evaluation of the expression
         */
        evaluate: function(expression, values) {
            values = values || {};
            return this.parse(expression, Object.keys(values)).getNodes().evaluate(this.functions, values);
        },

        /**
         * Parses an expression.
         *
         * @param {Expression|string} expression  The expression to parse
         * @param {Array|Object} names  An array or hash of valid names
         *
         * @return {ParsedExpression}  A ParsedExpression instance
         */
        parse: function(expression, names) {
            if (expression instanceof ParsedExpression) {
                return expression;
            }

            var keys = Object.keys(names);

            keys.sort(function(a, b) {
                if (names[a] === names[b]) {
                    return 0;
                }
                return names[a] > names[b] ? 1 : -1;
            });

            var cacheKeyItems = keys.map(function(key) {
                var keyIsInt = key >= 0 && key % 1 === 0;
                return keyIsInt ? names[key] : key + ':' + names[key];
            });

            var key = expression + '//' + cacheKeyItems.join('|');

            var parsedExpression = this.cache.fetch(key);

            if (null === parsedExpression) {
                var nodes = this.getParser().parse(this.getLexer().tokenize(String(expression)), names);
                parsedExpression = new ParsedExpression(String(expression), nodes);
                this.cache.save(key, parsedExpression);
            }

            return parsedExpression;
        },

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
        register: function(name, compiler, evaluator) {
            if (null !== this.parser) {
                throw new Error(
                    'Registering functions after calling evaluate(), compile() or parse() is not supported.');
            }

            this.functions[name] = {
                compiler: compiler,
                evaluator: evaluator
            };
        },

        addFunction: function(func) {
            this.register(func.getName(), func.getCompiler(), func.getEvaluator());
        },

        /**
         * @param {ExpressionFunctionProviderInterface} provider
         */
        registerProvider: function(provider) {
            ExpressionFunctionProviderInterface.expectToBeImplementedBy(provider);
            provider.getFunctions().forEach(this.addFunction.bind(this));
        },

        registerFunctions: function() {
            // Since JS and PHP don't share any constants between each other you can't implement constant function
            // as it did in Symphony expression language library
        },

        getLexer: function() {
            if (null === this.lexer) {
                this.lexer = new Lexer();
            }

            return this.lexer;
        },

        getParser: function() {
            if (null === this.parser) {
                this.parser = new Parser(this.functions);
            }

            return this.parser;
        },

        getCompiler: function() {
            if (null === this.compiler) {
                this.compiler = new Compiler(this.functions);
            }

            return this.compiler.reset();
        }
    };

    return ExpressionLanguage;
});
