import Node from './node';

class ConstantNode extends Node {
    /**
     * @param {*} value a constant value
     */
    constructor(value) {
        super([], {value: value});
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        compiler.repr(this.attrs.value);
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        return this.attrs.value;
    }
}

export default ConstantNode;
