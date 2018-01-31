define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');

    /**
     * @param {string} name a name of variable
     */
    function NameNode(name) {
        NameNode.__super__.constructor.call(this, [], {name: name});
    }

    NameNode.prototype = Object.create(Node.prototype);
    NameNode.__super__ = Node.prototype;

    Object.assign(NameNode.prototype, {
        constructor: NameNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            compiler.raw(this.attrs.name);
        },

        /**
         * @inheritDoc
         */
        evaluate: function(functions, values) {
            return values[this.attrs.name];
        }
    });

    return NameNode;
});
