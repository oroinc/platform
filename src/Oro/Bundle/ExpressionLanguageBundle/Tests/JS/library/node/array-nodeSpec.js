import ArrayNode from 'oroexpressionlanguage/js/library/node/array-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/array-node', () => {
    let arrayNode;

    beforeEach(() => {
        arrayNode = new ArrayNode();
        arrayNode.addElement(new ConstantNode('a'), new ConstantNode('b'));
        arrayNode.addElement(new ConstantNode('b'));
    });

    it('evaluation', function() {
        expect(arrayNode.evaluate()).toEqual({b: 'a', 0: 'b'});
    });

    it('compilation', () => {
        const compiler = new Compiler({});
        arrayNode.compile(compiler);
        expect(compiler.getSource()).toBe('{"b": "a", 0: "b"}');
    });
});
