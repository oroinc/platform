define(function(require) {
    'use strict';

    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');

    function ArgumentsNode() {
        ArgumentsNode.__super__.call(this);
    }

    ArgumentsNode.prototype = Object.create(ArrayNode.prototype);
    ArgumentsNode.__super__ = ArrayNode;

    Object.assign(ArgumentsNode.prototype, {
        constructor: ArgumentsNode,

        /**
         * @inheritDoc
         */
        compile: function(compiler) {
            this.compileArguments(compiler, false);
        }
    });

    return ArgumentsNode;
});
