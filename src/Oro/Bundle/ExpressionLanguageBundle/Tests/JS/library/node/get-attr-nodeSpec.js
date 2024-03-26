import GetAttrNode from 'oroexpressionlanguage/js/library/node/get-attr-node';
import NameNode from 'oroexpressionlanguage/js/library/node/name-node';
import ConstantNode from 'oroexpressionlanguage/js/library/node/constant-node';
import ArrayNode from 'oroexpressionlanguage/js/library/node/array-node';
import ArgumentsNode from 'oroexpressionlanguage/js/library/node/arguments-node';
import Compiler from 'oroexpressionlanguage/js/library/compiler';

describe('oroexpressionlanguage/js/library/node/get-attr-node', () => {
    let compiler;

    beforeEach(() => {
        compiler = new Compiler({});
    });

    describe('array call by numeric index', () => {
        let node;
        beforeEach(() => {
            node = new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode(0),
                new ArgumentsNode(),
                GetAttrNode.ARRAY_CALL
            );
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {foo: {b: 'a', 0: 'b'}})).toEqual('b');
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo[0]');
        });
    });

    describe('array call by string index', () => {
        let node;
        beforeEach(() => {
            node = new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('b'),
                new ArgumentsNode(),
                GetAttrNode.ARRAY_CALL
            );
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {foo: {b: 'a', 0: 'b'}})).toEqual('a');
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo["b"]');
        });
    });

    describe('property call', () => {
        let node;
        beforeEach(() => {
            node = new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('foo'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {foo: {foo: 'bar'}})).toEqual('bar');
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo.foo');
        });
    });

    describe('method call', () => {
        let node;
        beforeEach(() => {
            node = new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('quz'),
                (() => {
                    const arrayNode = new ArrayNode();
                    arrayNode.addElement(new ConstantNode('a'), new ConstantNode('b'));
                    arrayNode.addElement(new ConstantNode('b'));
                    const argsNode = new ArgumentsNode();
                    argsNode.addElement(arrayNode);
                    return argsNode;
                })(),
                GetAttrNode.METHOD_CALL
            );
        });

        it('evaluation', () => {
            const obj = {
                quz(param) {
                    return `${param.b},${param[0]}`;
                }
            };
            expect(node.evaluate({}, {foo: obj})).toEqual('a,b');
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo.quz({"b": "a", 0: "b"})');
        });
    });

    describe('property call by variable name', () => {
        let node;
        beforeEach(() => {
            node = new GetAttrNode(
                new NameNode('foo'),
                new NameNode('index'),
                new ArgumentsNode(),
                GetAttrNode.ARRAY_CALL
            );
        });

        it('evaluation', () => {
            expect(node.evaluate({}, {foo: {b: 'a', 0: 'b'}, index: 'b'})).toEqual('a');
        });

        it('compilation', () => {
            node.compile(compiler);
            expect(compiler.getSource()).toBe('foo[index]');
        });
    });
});
