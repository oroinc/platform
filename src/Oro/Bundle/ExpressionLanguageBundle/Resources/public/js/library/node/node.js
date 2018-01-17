define(function() {
    'use strict';

    /**
     * @param {Array} [nodes] an array of nodes
     * @param {Object} [attrs] an list of attributes key-value pairs
     */
    function Node(nodes, attrs) {
        this.nodes = nodes || [];
        this.attrs = attrs || {};
    }

    Node.prototype = {
        constructor: Node,

        toString: function() {
            var repr;
            var attrs = [];

            for (var name in this.attrs) {
                if (this.attrs.hasOwnProperty(name)) {
                    attrs.push(name + ': ' + JSON.stringify(this.attrs[name]));
                }
            }

            repr = [this.constructor.name + '(' + attrs.join(', ')];

            if (this.nodes.length) {
                this.nodes.forEach(function(node) {
                    var lines = String(node)
                        .split('\n')
                        .map(function(line) {
                            return '    ' + line;
                        });
                    repr.push.apply(repr, lines);
                });
                repr.push(')');
            } else {
                repr[repr.length - 1] += ')';
            }

            return repr.join('\n');
        },

        /**
         * @param {Compiler} compiler
         */
        compile: function(compiler) {
            this.nodes.forEach(function(node) {
                node.compile(compiler);
            });
        },

        /**
         * @param {Object} functions
         * @param {Object} values
         * @return {any[]}
         */
        evaluate: function(functions, values) {
            return this.nodes.map(function(node) {
                return node.evaluate(functions, values);
            });
        }
    };

    return Node;
});
