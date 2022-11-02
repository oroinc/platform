class ExpressionFunction {
    /**
     * @param {string} name  The function name
     * @param {Function} compiler  A callback to compile the function
     * @param {Function} evaluator  A callback to evaluate the function
     */
    constructor(name, compiler, evaluator) {
        this.name = name;
        this.compiler = compiler;
        this.evaluator = evaluator;
    }

    getName() {
        return this.name;
    }

    getCompiler() {
        return this.compiler;
    }

    getEvaluator() {
        return this.evaluator;
    }
}

export default ExpressionFunction;
