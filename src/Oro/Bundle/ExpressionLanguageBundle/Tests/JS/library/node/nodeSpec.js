define(function(require) {
    'use strict';

    var Node = require('oroexpressionlanguage/js/library/node/node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');

    describe('oroexpressionlanguage/js/library/node/node', function() {
        it('empty node', function() {
            var node = new Node();
            expect(String(node)).toBe('Node()');
        });

        it('node with attributes', function() {
            var attrs = {
                name: 'foo',
                isActive: true,
                data: {
                    color: 'red'
                }
            };
            var node = new Node([], attrs);
            expect(String(node)).toBe('Node(name: "foo", isActive: true, data: {"color":"red"})');
        });

        describe('node with sub-nodes', function() {
            var node;
            var subNode;

            beforeEach(function() {
                subNode = new ConstantNode('foo');
                spyOn(subNode, 'compile');
                spyOn(subNode, 'evaluate').and.returnValue(42);

                node = new Node([subNode]);
            });

            it('string representation', function() {
                var str = ['Node(', '    ConstantNode(value: "foo")', ')'].join('\n');
                expect(String(node)).toBe(str);
            });

            it('compilation', function() {
                var compilerMock = {};
                node.compile(compilerMock);
                expect(subNode.compile).toHaveBeenCalledWith(compilerMock);
            });

            it('evaluation', function() {
                var functions = {};
                var values = {};
                var result = node.evaluate(functions, values);
                expect(subNode.evaluate).toHaveBeenCalledWith(functions, values);
                expect(result).toEqual([42]);
            });
        });
    });
});
