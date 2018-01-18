define(function(require) {
    'use strict';

    var FunctionNode = require('oroexpressionlanguage/js/library/node/function-node');
    var Node = require('oroexpressionlanguage/js/library/node/node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var Compiler = require('oroexpressionlanguage/js/library/compiler');

    describe('oroexpressionlanguage/js/library/node/function-node', function() {
        var node;
        var functions;

        beforeEach(function() {
            node = new FunctionNode('foo', new Node([new ConstantNode('bar')]));
            functions = {
                foo: {
                    compiler: function(arg) {
                        return 'foo(' + arg + ')';
                    },
                    evaluator: function(variables, arg) {
                        return arg;
                    }
                }
            };
        });

        it('evaluation', function() {
            expect(node.evaluate(functions, {})).toBe('bar');
        });

        it('compilation', function() {
            var compiler = new Compiler(functions);
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo("bar")');
        });
    });
});
