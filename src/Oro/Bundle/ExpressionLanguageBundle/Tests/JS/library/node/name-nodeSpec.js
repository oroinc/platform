define(function(require) {
    'use strict';

    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/name-node', function() {
        var node;

        beforeEach(function() {
            node = new NameNode('foo');
        });

        it('evaluation', function() {
            expect(node.evaluate({}, {foo: 'bar'})).toBe('bar');
        });

        it('compilation', function() {
            var compiler = new Compiler({});
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo');
        });
    });
});
