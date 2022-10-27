import Node from './node';

class NameNode extends Node {
    /**
     * @param {string} name a name of variable
     */
    constructor(name) {
        super([], {name: name});
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        compiler.raw(this.attrs.name);
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        return values[this.attrs.name];
    }
}

export default NameNode;
