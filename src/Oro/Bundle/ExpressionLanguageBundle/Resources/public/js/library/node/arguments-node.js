define(function(require) {
    'use strict';

    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');

    function ArgumentsNode() {
        ArgumentsNode.__super__.constructor.call(this);
    }

    ArgumentsNode.prototype = Object.create(ArrayNode.prototype);
    ArgumentsNode.__super__ = ArrayNode.prototype;

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
