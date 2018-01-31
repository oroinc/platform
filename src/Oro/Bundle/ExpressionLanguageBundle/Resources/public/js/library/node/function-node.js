define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');

    /**
     * @param {string} name a name of function
     * @param {Node} args arguments of function
     * @constructor
     */
    function FunctionNode(name, args) {
        FunctionNode.__super__.constructor.call(this, [args], {name: name});
    }

    FunctionNode.prototype = Object.create(Node.prototype);
    FunctionNode.__super__ = Node.prototype;

    Object.assign(FunctionNode.prototype, {
        constructor: FunctionNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            var args = this.nodes[0].nodes.map(function(node) {
                return compiler.subcompile(node);
            });

            var func = compiler.getFunction(this.attrs.name);

            compiler.raw(func.compiler.apply(null, args));
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            var args = this.nodes[0].nodes.map(function(node) {
                return node.evaluate(functions, values);
            });
            args.unshift([values]);

            var func = functions[this.attrs.name];

            return func.evaluator.apply(null, args);
        }
    });

    return FunctionNode;
});
