define(function() {
    'use strict';

    /**
     * @param {string} name  The function name
     * @param {Function} compiler  A callback to compile the function
     * @param {Function} evaluator  A callback to evaluate the function
     */
    function ExpressionFunction(name, compiler, evaluator) {
        this.name = name;
        this.compiler = compiler;
        this.evaluator = evaluator;
    }

    ExpressionFunction.prototype = {
        constructor: ExpressionFunction,

        getName: function() {
            return this.name;
        },

        getCompiler: function() {
            return this.compiler;
        },

        getEvaluator: function() {
            return this.evaluator;
        }
    };

    return ExpressionFunction;
});
