import Node from './node';
import ConstantNode from './constant-node';

class ArrayNode extends Node {
    constructor() {
        super();
        this.index = -1;
    }

    /**
     * Adds value and key nodes as sub-nodes of array node
     *
     * @param {Node} value
     * @param {Node} [key]
     */
    addElement(value, key) {
        if (key === void 0) {
            key = new ConstantNode(++this.index);
        }

        this.nodes.push(key, value);
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        compiler.raw('{');
        this.compileArguments(compiler);
        compiler.raw('}');
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        const result = {};
        const pairs = this.getKeyValuePairs();
        pairs.forEach(pair => {
            const key = pair.key.evaluate(functions, values);
            result[key] = pair.value.evaluate(functions, values);
        });
        return result;
    }

    /**
     * @return {Array.<{key: string, value: *}>}
     * @protected
     */
    getKeyValuePairs() {
        const pairs = [];
        this.nodes.forEach((node, i) => {
            if (i % 2) {
                pairs[pairs.length - 1].value = node;
            } else {
                pairs.push({key: node});
            }
        });
        return pairs;
    }

    /**
     * @param {Compiler} compiler
     * @param {boolean} [withKeys]
     * @protected
     */
    compileArguments(compiler, withKeys) {
        if (withKeys === void 0) {
            withKeys = true;
        }
        const pairs = this.getKeyValuePairs();
        pairs.forEach((pair, i) => {
            if (i !== 0) {
                compiler.raw(', ');
            }

            if (withKeys) {
                compiler
                    .compile(pair.key)
                    .raw(': ');
            }

            compiler.compile(pair.value);
        });
    }
}

export default ArrayNode;
