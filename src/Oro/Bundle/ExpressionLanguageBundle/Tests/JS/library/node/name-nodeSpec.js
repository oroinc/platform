import NameNode from 'oroexpressionlanguage/js/library/node/name-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/name-node', () => {
    let node;

    beforeEach(() => {
        node = new NameNode('foo');
    });

    it('evaluation', () => {
        expect(node.evaluate({}, {foo: 'bar'})).toBe('bar');
    });

    it('compilation', () => {
        const compiler = new Compiler({});
        node.compile(compiler);
        expect(compiler.getSource()).toBe('foo');
    });
});
