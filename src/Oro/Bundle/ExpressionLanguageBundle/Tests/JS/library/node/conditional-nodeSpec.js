import ConditionalNode from 'oroexpressionlanguage/js/library/node/conditional-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/conditional-node', () => {
    let compiler;

    beforeEach(() => {
        compiler = new Compiler({});
    });

    describe('then case', () => {
        let conditionalNode;

        beforeEach(() => {
            conditionalNode = new ConditionalNode(
                new ConstantNode(true),
                new ConstantNode(1),
                new ConstantNode(2)
            );
        });

        it('evaluation', () => {
            expect(conditionalNode.evaluate()).toEqual(1);
        });

        it('compilation', () => {
            conditionalNode.compile(compiler);
            expect(compiler.getSource()).toBe('((true) ? (1) : (2))');
        });
    });

    describe('else case', () => {
        let conditionalNode;

        beforeEach(() => {
            conditionalNode = new ConditionalNode(
                new ConstantNode(false),
                new ConstantNode(1),
                new ConstantNode(2)
            );
        });

        it('evaluation', () => {
            expect(conditionalNode.evaluate()).toEqual(2);
        });

        it('compilation', () => {
            conditionalNode.compile(compiler);
            expect(compiler.getSource()).toBe('((false) ? (1) : (2))');
        });
    });
});
