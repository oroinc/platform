import UnaryNode from 'oroexpressionlanguage/js/library/node/unary-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/unary-node', () => {
    let compiler;

    beforeEach(() => {
        compiler = new Compiler({});
    });

    describe('negative', () => {
        let node;
        beforeEach(() => {
            node = new UnaryNode('-', new ConstantNode(1));
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {})).toEqual(-1);
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('(-1)');
        });
    });

    describe('positive', () => {
        let node;
        beforeEach(() => {
            node = new UnaryNode('+', new ConstantNode(3));
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {})).toEqual(3);
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('(+3)');
        });
    });

    describe('negation over !', () => {
        let node;
        beforeEach(() => {
            node = new UnaryNode('!', new ConstantNode(true));
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {})).toEqual(false);
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('(!true)');
        });
    });

    describe('negation over "not"', () => {
        let node;
        beforeEach(() => {
            node = new UnaryNode('not', new ConstantNode(true));
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {})).toEqual(false);
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('(!true)');
        });
    });
});
