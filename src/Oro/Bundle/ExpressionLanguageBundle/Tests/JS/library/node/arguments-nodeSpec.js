import ArgumentsNode from 'oroexpressionlanguage/js/library/node/arguments-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/arguments-node', () => {
    let argumentsNode;

    beforeEach(() => {
        argumentsNode = new ArgumentsNode();
        argumentsNode.addElement(new ConstantNode('a'), new ConstantNode('b'));
        argumentsNode.addElement(new ConstantNode('b'));
    });

    it('compilation', () => {
        const compiler = new Compiler({});
        argumentsNode.compile(compiler);
        expect(compiler.getSource()).toBe('"a", "b"');
    });
});
