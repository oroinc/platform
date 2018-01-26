define(function(require) {
    'use strict';

    var ArrayNode = require('oroexpressionlanguage/js/library/node/array-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/array-node', function() {
        var arrayNode;

        beforeEach(function() {
            arrayNode = new ArrayNode();
            arrayNode.addElement(new ConstantNode('a'), new ConstantNode('b'));
            arrayNode.addElement(new ConstantNode('b'));
        });

        it('evaluation', function() {
            expect(arrayNode.evaluate()).toEqual({b: 'a', 0: 'b'});
        });

        it('compilation', function() {
            var compiler = new Compiler({});
            arrayNode.compile(compiler);
            expect(compiler.getSource()).toBe('{"b": "a", 0: "b"}');
        });
    });
});
