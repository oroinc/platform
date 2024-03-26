import Node from './node';

class GetAttrNode extends Node {
    static name = 'GetAttrNode';

    static PROPERTY_CALL = 1;
    static METHOD_CALL = 2;
    static ARRAY_CALL = 3;

    /**
     * @param {Node} node
     * @param {Node} attr
     * @param {ArrayNode} args
     * @param {number} type
     */
    constructor(node, attr, args, type) {
        super([node, attr, args], {type: type});
    }

    /**
     * @inheritDoc
     */
    compile(compiler) {
        switch (this.attrs.type) {
            case GetAttrNode.PROPERTY_CALL:
                compiler
                    .compile(this.nodes[0])
                    .raw('.')
                    .raw(this.nodes[1].attrs.value);
                break;

            case GetAttrNode.METHOD_CALL:
                compiler
                    .compile(this.nodes[0])
                    .raw('.')
                    .raw(this.nodes[1].attrs.value)
                    .raw('(')
                    .compile(this.nodes[2])
                    .raw(')');
                break;

            case GetAttrNode.ARRAY_CALL:
                compiler
                    .compile(this.nodes[0])
                    .raw('[')
                    .compile(this.nodes[1])
                    .raw(']');
                break;
        }
    }

    /**
     * @inheritDoc
     */
    evaluate(functions, values) {
        let context;
        let property;
        switch (this.attrs.type) {
            case GetAttrNode.PROPERTY_CALL:
                context = this.nodes[0].evaluate(functions, values);
                if (typeof context !== 'object') {
                    throw new TypeError('Unable to get a property on a non-object.');
                }
                property = this.nodes[1].attrs.value;
                return context[property];

            case GetAttrNode.METHOD_CALL:
                context = this.nodes[0].evaluate(functions, values);
                if (typeof context !== 'object') {
                    throw new TypeError('Unable to get a property on a non-object.');
                }
                property = this.nodes[1].attrs.value;
                if (typeof context[property] !== 'function') {
                    throw new TypeError(`Unable to call method "${property}" of object` +
                        (context.constructor ? ` "${context.constructor.name}"` : '') + '.');
                }
                return context[property](...this.nodes[2].evaluate(functions, values));

            case GetAttrNode.ARRAY_CALL:
                context = this.nodes[0].evaluate(functions, values);
                if (typeof context !== 'object') {
                    throw new TypeError('Unable to get an element of non-array or a property on a non-object.');
                }
                property = this.nodes[1].evaluate(functions, values);
                return context[property];
        }
    }
}

export default GetAttrNode;
