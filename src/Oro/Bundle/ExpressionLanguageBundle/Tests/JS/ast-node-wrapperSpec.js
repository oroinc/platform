define(function(require) {
    'use strict';

    var ASTNodeWrapper = require('oroexpressionlanguage/js/ast-node-wrapper');
    var BinaryNode = require('oroexpressionlanguage/js/library/node/binary-node');
    var GetAttrNode = require('oroexpressionlanguage/js/library/node/get-attr-node');
    var ConstantNode = require('oroexpressionlanguage/js/library/node/constant-node');
    var NameNode = require('oroexpressionlanguage/js/library/node/name-node');
    var ArgumentsNode = require('oroexpressionlanguage/js/library/node/arguments-node');

    describe('oroexpressionlanguage/js/ast-node-wrapper', function() {
        describe('wraps single node', function() {
            var astNodeWrapper;
            beforeEach(function() {
                astNodeWrapper = new ASTNodeWrapper(new ConstantNode(1));
            });

            it('and gets `value` attribute', function() {
                expect(astNodeWrapper.attr('value')).toBe(1);
            });

            it('and checks type of node', function() {
                expect(astNodeWrapper.instanceOf(ConstantNode)).toBeTruthy();
            });
        });

        describe('wraps node with children', function() {
            var astNodeWrapper;

            beforeEach(function() {
                var nodes = new BinaryNode('*', new ConstantNode(1), new ConstantNode(2));
                astNodeWrapper = new ASTNodeWrapper(nodes);
            });

            it('and gets its children', function() {
                expect(astNodeWrapper.child(0).originNode).toEqual(new ConstantNode(1));
                expect(astNodeWrapper.child(1).originNode).toEqual(new ConstantNode(2));
            });
        });

        describe('wraps complicated node tree', function() {
            var astNodeWrapper;

            beforeEach(function() {
                // parsed expression `foo[1].bar != baz.qux`
                astNodeWrapper = new ASTNodeWrapper(
                    new BinaryNode('!=',
                        new GetAttrNode(
                            new GetAttrNode(
                                new NameNode('foo'),
                                new ConstantNode(1),
                                new ArgumentsNode(),
                                3
                            ),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            1
                        ),
                        new GetAttrNode(
                            new NameNode('baz'),
                            new ConstantNode('qux'),
                            new ArgumentsNode(),
                            1
                        )
                    )
                );
            });

            it('finds inside it node of a particular type', function() {
                var nameNodes = astNodeWrapper.findInstancesOf(NameNode);
                expect(nameNodes.length).toBe(2);
                expect(nameNodes[0].originNode).toEqual(new NameNode('foo'));
                expect(nameNodes[1].originNode).toEqual(new NameNode('baz'));
            });

            it('finds inside it node by particular callback', function() {
                var nameNodes = astNodeWrapper.findAll(function(node) {
                    return node.attr('value') === 'qux';
                });
                expect(nameNodes.length).toBe(1);
                expect(nameNodes[0].originNode).toEqual(new ConstantNode('qux'));
            });
        });
    });
});
