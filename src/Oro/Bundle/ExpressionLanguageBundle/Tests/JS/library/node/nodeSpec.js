import Node from 'oroexpressionlanguage/js/library/node/node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';

describe('oroexpressionlanguage/js/library/node/node', () => {
    it('empty node', () => {
        const node = new Node();
        expect(String(node)).toBe('Node()');
    });

    it('node with attributes', () => {
        const attrs = {
            name: 'foo',
            isActive: true,
            data: {
                color: 'red'
            }
        };
        const node = new Node([], attrs);
        expect(String(node)).toBe('Node(name: "foo", isActive: true, data: {"color":"red"})');
    });

    describe('node with sub-nodes', () => {
        let node;
        let subNode;

        beforeEach(() => {
            subNode = new ConstantNode('foo');
            spyOn(subNode, 'compile');
            spyOn(subNode, 'evaluate').and.returnValue(42);

            node = new Node([subNode]);
        });

        it('string representation', () => {
            const str = ['Node(', '    ConstantNode(value: "foo")', ')'].join('\n');
            expect(String(node)).toBe(str);
        });

        it('compilation', () => {
            const compilerMock = {};
            node.compile(compilerMock);
            expect(subNode.compile).toHaveBeenCalledWith(compilerMock);
        });

        it('evaluation', () => {
            const functions = {};
            const values = {};
            const result = node.evaluate(functions, values);
            expect(subNode.evaluate).toHaveBeenCalledWith(functions, values);
            expect(result).toEqual([42]);
        });
    });
});
