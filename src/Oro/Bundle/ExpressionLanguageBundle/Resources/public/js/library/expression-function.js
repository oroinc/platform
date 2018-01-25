define(function() {
    'use strict';

    /**
     * @param {string} name  The function name
     * @param {Object} compiler  An object that exists a compile method to compile the function
     * @param {Object} evaluator  An object that exists an evaluate method to evaluate the function
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
