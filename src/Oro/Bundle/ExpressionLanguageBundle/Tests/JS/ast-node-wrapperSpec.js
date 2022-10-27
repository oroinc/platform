import ASTNodeWrapper from 'oroexpressionlanguage/js/ast-node-wrapper';
import BinaryNode from 'oroexpressionlanguage/js/library/node/binary-node';
import GetAttrNode from 'oroexpressionlanguage/js/library/node/get-attr-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import NameNode from 'oroexpressionlanguage/js/library/node/name-node';
import ArgumentsNode from 'oroexpressionlanguage/js/library/node/arguments-node';

describe('oroexpressionlanguage/js/ast-node-wrapper', () => {
    describe('wraps single node', () => {
        let astNodeWrapper;
        beforeEach(() => {
            astNodeWrapper = new ASTNodeWrapper(new ConstantNode(1));
        });

        it('and gets `value` attribute', () => {
            expect(astNodeWrapper.attr('value')).toBe(1);
        });

        it('and checks type of node', () => {
            expect(astNodeWrapper.instanceOf(ConstantNode)).toBeTruthy();
        });
    });

    describe('wraps node with children', () => {
        let astNodeWrapper;

        beforeEach(() => {
            const nodes = new BinaryNode('*', new ConstantNode(1), new ConstantNode(2));
            astNodeWrapper = new ASTNodeWrapper(nodes);
        });

        it('and gets its children', () => {
            expect(astNodeWrapper.child(0).originNode).toEqual(new ConstantNode(1));
            expect(astNodeWrapper.child(1).originNode).toEqual(new ConstantNode(2));
        });
    });

    describe('wraps complicated node tree', () => {
        let astNodeWrapper;

        beforeEach(() => {
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

        it('finds inside it node of a particular type', () => {
            const nameNodes = astNodeWrapper.findInstancesOf(NameNode);
            expect(nameNodes.length).toBe(2);
            expect(nameNodes[0].originNode).toEqual(new NameNode('foo'));
            expect(nameNodes[1].originNode).toEqual(new NameNode('baz'));
        });

        it('finds inside it node by particular callback', () => {
            const nameNodes = astNodeWrapper.findAll(node => node.attr('value') === 'qux');
            expect(nameNodes.length).toBe(1);
            expect(nameNodes[0].originNode).toEqual(new ConstantNode('qux'));
        });
    });
});
