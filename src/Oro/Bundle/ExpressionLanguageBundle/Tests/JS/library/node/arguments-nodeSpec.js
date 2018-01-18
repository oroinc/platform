define(function(require) {
    'use strict';

    var ArgumentsNode = require('oroexpressionlanguage/js/library/node/arguments-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/arguments-node', function() {
        var argumentsNode;

        beforeEach(function() {
            argumentsNode = new ArgumentsNode();
            argumentsNode.addElement(new ConstantNode('a'), new ConstantNode('b'));
            argumentsNode.addElement(new ConstantNode('b'));
        });

        it('compilation', function() {
            var compiler = new Compiler({});
            argumentsNode.compile(compiler);
            expect(compiler.getSource()).toBe('"a", "b"');
        });
    });
});
