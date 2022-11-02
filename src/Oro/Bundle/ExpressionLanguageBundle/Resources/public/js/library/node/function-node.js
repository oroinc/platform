import Node from './node';

class FunctionNode extends Node {
    /**
     * @param {string} name a name of function
     * @param {Node} args arguments of function
     * @constructor
     */
    constructor(name, args) {
        super([args], {name: name});
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        const args = this.nodes[0].nodes.map(node => compiler.subcompile(node));
        const func = compiler.getFunction(this.attrs.name);
        compiler.raw(func.compiler(...args));
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        const args = this.nodes[0].nodes.map(node => node.evaluate(functions, values));
        args.unshift([values]);

        const func = functions[this.attrs.name];

        return func.evaluator(...args);
    }
}

export default FunctionNode;
