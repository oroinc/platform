import {ArgumentsNode, ArrayNode, BinaryNode, ConditionalNode, ConstantNode, FunctionNode,
    GetAttrNode, NameNode, Node, UnaryNode} from 'oroexpressionlanguage/js/expression-language-library';
import {isIndexedArrayNode, createArrayNode, createGetAttrNode, createFunctionNode, compareAST, cloneAST}
    from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

describe('oroexpressionlanguage/js/expression-language-tools', () => {
    describe('isIndexedArrayNode', () => {
        const cases = {
            'indexed array': [
                (() => {
                    const arr = new ArrayNode();
                    arr.addElement(new ConstantNode('foo'));
                    arr.addElement(new ConstantNode(null));
                    arr.addElement(new ConstantNode(42));
                    arr.addElement(new ConstantNode(3.14));
                    return arr;
                })(),
                true
            ],
            'associative array': [
                (() => {
                    const arr = new ArrayNode();
                    arr.addElement(new ConstantNode('foo'), new ConstantNode('text'));
                    arr.addElement(new ConstantNode(null), new ConstantNode('empty'));
                    arr.addElement(new ConstantNode(42), new ConstantNode('sense'));
                    arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                    return arr;
                })(),
                false
            ],
            'mixed array': [
                (() => {
                    const arr = new ArrayNode();
                    arr.addElement(new ConstantNode('foo'), new ConstantNode('text'));
                    arr.addElement(new ConstantNode(null));
                    arr.addElement(new ConstantNode(42));
                    arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                    return arr;
                })(),
                false
            ]
        };

        jasmine.itEachCase(cases, (node, expectedResult) => {
            expect(isIndexedArrayNode(node)).toEqual(expectedResult);
        });
    });

    describe('createArrayNode', () => {
        const cases = {
            'indexed array': [
                ['foo', null, 42, 3.14],
                (() => {
                    const arr = new ArrayNode();
                    arr.addElement(new ConstantNode('foo'));
                    arr.addElement(new ConstantNode(null));
                    arr.addElement(new ConstantNode(42));
                    arr.addElement(new ConstantNode(3.14));
                    return arr;
                })()
            ],
            'associative array': [
                {text: 'foo', empty: null, sense: 42, pi: 3.14},
                (() => {
                    const arr = new ArrayNode();
                    arr.addElement(new ConstantNode('foo'), new ConstantNode('text'));
                    arr.addElement(new ConstantNode(null), new ConstantNode('empty'));
                    arr.addElement(new ConstantNode(42), new ConstantNode('sense'));
                    arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                    return arr;
                })()
            ],
            'mixed array': [
                [['text', new NameNode('foo')], null, 42, [new ConstantNode('pi'), 3.14]],
                (() => {
                    const arr = new ArrayNode();
                    arr.addElement(new NameNode('foo'), new ConstantNode('text'));
                    arr.addElement(new ConstantNode(null));
                    arr.addElement(new ConstantNode(42));
                    arr.addElement(new ConstantNode(3.14), new ConstantNode('pi'));
                    return arr;
                })()
            ]
        };

        jasmine.itEachCase(cases, (data, expectedResult) => {
            expect(createArrayNode(data)).toEqual(expectedResult);
        });
    });

    describe('createGetAttrNode', () => {
        const cases = {
            'one element in list': [
                ['foo'],
                'foo',
                new NameNode('foo')
            ],
            'two elements in list': [
                ['foo', 'bar'],
                'foo.bar',
                new GetAttrNode(
                    new NameNode('foo'),
                    new ConstantNode('bar'),
                    new ArgumentsNode(),
                    GetAttrNode.PROPERTY_CALL
                )
            ],
            'four elements in list': [
                ['foo', 'bar', 'baz', 'qoo'],
                'foo.bar.baz.qoo',
                new GetAttrNode(
                    new GetAttrNode(
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        new ConstantNode('baz'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    ),
                    new ConstantNode('qoo'),
                    new ArgumentsNode(),
                    GetAttrNode.PROPERTY_CALL
                )
            ]
        };

        jasmine.itEachCase(cases, (array, string, expectedResult) => {
            expect(createGetAttrNode(array)).toEqual(expectedResult);
            expect(createGetAttrNode(string)).toEqual(expectedResult);
        });
    });

    describe('createFunctionNode', () => {
        const cases = {
            'function without arguments': [
                'foo',
                void 0,
                new FunctionNode('foo', new Node([]))
            ],
            'function with arguments': [
                'foo',
                ['bar', null, true, 3.14, new NameNode('bar')],
                new FunctionNode('foo', new Node([
                    new ConstantNode('bar'),
                    new ConstantNode(null),
                    new ConstantNode(true),
                    new ConstantNode(3.14),
                    new NameNode('bar')
                ]))
            ]
        };

        jasmine.itEachCase(cases, (funcName, funcArgs, expectedResult) => {
            expect(createFunctionNode(funcName, funcArgs)).toEqual(expectedResult);
        });
    });

    describe('compareAST', () => {
        const cases = {
            'different types of node': [
                // first node
                (() => {
                    const node = new ArgumentsNode();
                    node.addElement(new ConstantNode('foo'));
                    return node;
                })(),
                // second node
                (() => {
                    const node = new ArrayNode();
                    node.addElement(new ConstantNode('foo'));
                    return node;
                })(),
                // result
                false
            ],
            'compare with not a node': [
                ['foo'],
                (() => {
                    const node = new ArrayNode();
                    node.addElement(new ConstantNode('foo'));
                    return node;
                })(),
                false
            ],
            'different attributes': [
                new ConstantNode(42),
                new ConstantNode('42'),
                false
            ],
            'different sub-nodes': [
                new FunctionNode('foo', new Node([
                    new ConstantNode('bar')
                ])),
                new FunctionNode('foo', new Node([
                    new NameNode('bar')
                ])),
                false
            ],
            'identical AST': [
                new FunctionNode('foo', new Node([
                    (() => {
                        const node = new ArrayNode();
                        node.addElement(new ConstantNode('foo'));
                        return node;
                    })()
                ])),
                new FunctionNode('foo', new Node([
                    (() => {
                        const node = new ArrayNode();
                        node.addElement(new ConstantNode('foo'));
                        return node;
                    })()
                ])),
                true
            ]
        };

        jasmine.itEachCase(cases, (firstNode, secondNode, expectedResult) => {
            expect(compareAST(firstNode, secondNode)).toEqual(expectedResult);
        });
    });

    describe('cloneAST', () => {
        let originalAST;
        let clonedAST;

        beforeEach(() => {
            originalAST = new ConditionalNode(
                new BinaryNode(
                    '>=',
                    new NameNode('quz'),
                    new ConstantNode(42)
                ),
                new GetAttrNode(
                    new GetAttrNode(
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        new ConstantNode('baz'),
                        new ArgumentsNode(),
                        GetAttrNode.ARRAY_CALL
                    ),
                    new ConstantNode('baq'),
                    (() => {
                        const node = new ArgumentsNode();
                        node.addElement(new NameNode('bar'));
                        node.addElement(new ConstantNode(42));
                        return node;
                    })(),
                    GetAttrNode.METHOD_CALL
                ),
                new FunctionNode('foo', new Node([
                    new UnaryNode('not', new NameNode('bar')),
                    (() => {
                        const node = new ArrayNode();
                        node.addElement(new ConstantNode('foo'));
                        node.addElement(new NameNode('bar'), new ConstantNode(true));
                        return node;
                    })()
                ]))
            );
            clonedAST = cloneAST(originalAST);
        });

        it('AST is identical', () => {
            expect(clonedAST).toEqual(originalAST);
        });

        it('ConditionalNode is a different instances', () => {
            expect(clonedAST).toEqual(jasmine.any(ConditionalNode));
            expect(clonedAST).not.toBe(originalAST);
        });

        it('BinaryNode is a different instances', () => {
            expect(clonedAST.nodes[0]).toEqual(jasmine.any(BinaryNode));
            expect(clonedAST.nodes[0]).not.toBe(originalAST.nodes[0]);
        });

        it('NameNode is a different instances', () => {
            expect(clonedAST.nodes[0].nodes[0]).toEqual(jasmine.any(NameNode));
            expect(clonedAST.nodes[0].nodes[0]).not.toBe(originalAST.nodes[0].nodes[0]);
        });

        it('ConstantNode is a different instances', () => {
            expect(clonedAST.nodes[0].nodes[1]).toEqual(jasmine.any(ConstantNode));
            expect(clonedAST.nodes[0].nodes[1]).not.toBe(originalAST.nodes[0].nodes[1]);
        });

        it('GetAttrNode is a different instances', () => {
            expect(clonedAST.nodes[1]).toEqual(jasmine.any(GetAttrNode));
            expect(clonedAST.nodes[1]).not.toBe(originalAST.nodes[1]);
        });

        it('ArgumentsNode is a different instances', () => {
            expect(clonedAST.nodes[1].nodes[2]).toEqual(jasmine.any(ArgumentsNode));
            expect(clonedAST.nodes[1].nodes[2]).not.toBe(originalAST.nodes[1].nodes[2]);
        });

        it('FunctionNode is a different instances', () => {
            expect(clonedAST.nodes[2]).toEqual(jasmine.any(FunctionNode));
            expect(clonedAST.nodes[2]).not.toBe(originalAST.nodes[2]);
        });

        it('Node is a different instances', () => {
            expect(clonedAST.nodes[2].nodes[0]).toEqual(jasmine.any(Node));
            expect(clonedAST.nodes[2].nodes[0]).not.toBe(originalAST.nodes[2].nodes[0]);
        });

        it('UnaryNode is a different instances', () => {
            expect(clonedAST.nodes[2].nodes[0].nodes[0]).toEqual(jasmine.any(UnaryNode));
            expect(clonedAST.nodes[2].nodes[0].nodes[0]).not.toBe(originalAST.nodes[2].nodes[0].nodes[0]);
        });

        it('ArrayNode is a different instances', () => {
            expect(clonedAST.nodes[2].nodes[0].nodes[1]).toEqual(jasmine.any(ArrayNode));
            expect(clonedAST.nodes[2].nodes[0].nodes[1]).not.toBe(originalAST.nodes[2].nodes[0].nodes[1]);
        });
    });
});
