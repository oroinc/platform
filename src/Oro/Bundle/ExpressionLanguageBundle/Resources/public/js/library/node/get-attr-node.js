define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');

    /**
     * @param {Node} node
     * @param {Node} attr
     * @param {ArrayNode} args
     * @param {number} type
     */
    function GetAttrNode(node, attr, args, type) {
        GetAttrNode.__super__.call(this, [node, attr, args], {type: type});
    }

    GetAttrNode.prototype = Object.create(Node.prototype);
    GetAttrNode.__super__ = Node;
    Object.defineProperties(GetAttrNode, {
        PROPERTY_CALL: {value: 1},
        METHOD_CALL: {value: 2},
        ARRAY_CALL: {value: 3}
    });

    Object.assign(GetAttrNode.prototype, {
        constructor: GetAttrNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
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
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            var context;
            var property;
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
                        throw new TypeError('Unable to call method "' + property + '" of object' +
                            (context.constructor ? '"' + context.constructor.name + '"' : '') + '.');
                    }
                    return context[property].apply(context, this.nodes[2].evaluate(functions, values));

                case GetAttrNode.ARRAY_CALL:
                    context = this.nodes[0].evaluate(functions, values);
                    if (typeof context !== 'object') {
                        throw new TypeError('Unable to get an element of non-array or a property on a non-object.');
                    }
                    property = this.nodes[1].evaluate(functions, values);
                    return context[property];
            }
        }
    });

    return GetAttrNode;
});
