import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createFunctionNode, createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import {resolveFieldAST, normalizeBoundaryConditionAST, normalizeFieldConditionAST}
    from 'oroquerydesigner/js/query-type-converter/from-expression/tools';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/from-expression/tools', () => {
    describe('negative cases for `resolveFieldAST`', () => {
        const cases = {
            'constant node': [new ConstantNode('test')],
            'get attribute node': [createGetAttrNode('foo.bar')],
            'binary operation for two constant nodes': [
                new BinaryNode('=', new ConstantNode('test'), new ConstantNode('2018-04-03'))
            ],
            'binary operation for get attribute node on a right side': [
                new BinaryNode('=', new ConstantNode('test'), createGetAttrNode('foo.bar'))
            ],
            'non conventional between condition': [
                new BinaryNode('and',
                    new BinaryNode('>=', new ConstantNode(1), createGetAttrNode('foo.bar')),
                    new BinaryNode('<=', new ConstantNode(17), createGetAttrNode('foo.bar'))
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = resolveFieldAST(ast);
            expect(actualAST).toBe(null);
        });
    });

    describe('positive cases for `resolveFieldAST`', () => {
        const cases = {
            'binary operation for get attribute node on a left side': [
                new BinaryNode('=', createGetAttrNode('foo.bar'), new ConstantNode('test'))
            ],
            'binary operation for get attribute node on a left side within function call': [
                new BinaryNode('=',
                    createFunctionNode('week', [createGetAttrNode('foo.bar')]),
                    new ConstantNode(3)
                )
            ],
            'conventional between condition': [
                new BinaryNode('and',
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(1)),
                    new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(17))
                )
            ],
            'conventional between condition with function call': [
                new BinaryNode('and',
                    new BinaryNode('>=',
                        createFunctionNode('week', [createGetAttrNode('foo.bar')]), new ConstantNode(1)),
                    new BinaryNode('<=',
                        createFunctionNode('week', [createGetAttrNode('foo.bar')]), new ConstantNode(3))
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = resolveFieldAST(ast);
            expect(actualAST).toEqual(createGetAttrNode('foo.bar'));
        });
    });

    describe('`normalizeFieldConditionAST` unaffected AST', () => {
        const cases = {
            'constant node': [
                new ConstantNode('test')
            ],
            'no invertible operation': [
                new BinaryNode('%', new ConstantNode(3), createGetAttrNode('foo.bar'))
            ],
            'two constants operation': [
                new BinaryNode('>', new ConstantNode(3), new ConstantNode(17))
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = normalizeFieldConditionAST(ast);
            expect(actualAST).toBe(ast);
        });
    });

    describe('`normalizeFieldConditionAST` changed AST', () => {
        const cases = {
            'invertible operation': [
                new BinaryNode('>', new ConstantNode(3), createGetAttrNode('foo.bar')),
                new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode(3))
            ],
            'condition with function call': [
                new BinaryNode('>=',
                    new ConstantNode(3),
                    createFunctionNode('week', [createGetAttrNode('foo.bar')])
                ),
                new BinaryNode('<=',
                    createFunctionNode('week', [createGetAttrNode('foo.bar')]),
                    new ConstantNode(3)
                )
            ]
        };

        jasmine.itEachCase(cases, (ast, expected) => {
            const actualAST = normalizeFieldConditionAST(ast);
            expect(actualAST).toEqual(expected);
        });
    });

    describe('`normalizeBoundaryConditionAST` unaffected AST', () => {
        const cases = {
            'different field': [
                new BinaryNode('and',
                    new BinaryNode('>', new ConstantNode(3), createGetAttrNode('foo.bar')),
                    new BinaryNode('>', createGetAttrNode('foo.que'), new ConstantNode(17))
                )
            ],
            'unconventional boundary condition': [
                new BinaryNode('and',
                    new BinaryNode('>=', new ConstantNode(3), createGetAttrNode('foo.bar')),
                    new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode(17))
                )
            ],
            'has nothing to normalize': [
                new BinaryNode('and',
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(3)),
                    new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(17))
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = normalizeBoundaryConditionAST(ast);
            expect(actualAST).toBe(ast);
        });
    });

    describe('`normalizeBoundaryConditionAST` changed `between` condition AST', () => {
        const cases = {
            'unconventional order of `between` conditions': [
                new BinaryNode('and',
                    new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(17)),
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(3))
                )
            ],
            'normalize field AST in `between` conditions': [
                new BinaryNode('and',
                    new BinaryNode('<=', new ConstantNode(3), createGetAttrNode('foo.bar')),
                    new BinaryNode('>=', new ConstantNode(17), createGetAttrNode('foo.bar'))
                )
            ],
            'normalize field AST in `between` conditions and order of `between` conditions': [
                new BinaryNode('and',
                    new BinaryNode('>=', new ConstantNode(17), createGetAttrNode('foo.bar')),
                    new BinaryNode('<=', new ConstantNode(3), createGetAttrNode('foo.bar'))
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = normalizeBoundaryConditionAST(ast);
            expect(actualAST).toEqual(
                new BinaryNode('and',
                    new BinaryNode('>=', createGetAttrNode('foo.bar'), new ConstantNode(3)),
                    new BinaryNode('<=', createGetAttrNode('foo.bar'), new ConstantNode(17))
                )
            );
        });
    });

    describe('`normalizeBoundaryConditionAST` changed `not between` condition AST', () => {
        const cases = {
            'unconventional order `not between` conditions': [
                new BinaryNode('and',
                    new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode(17)),
                    new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode(3))
                )
            ],
            'normalize field AST in `not between` conditions': [
                new BinaryNode('and',
                    new BinaryNode('>', new ConstantNode(3), createGetAttrNode('foo.bar')),
                    new BinaryNode('<', new ConstantNode(17), createGetAttrNode('foo.bar'))
                )
            ],
            'normalize field AST in `not between` conditions and order of `not between` conditions': [
                new BinaryNode('and',
                    new BinaryNode('<', new ConstantNode(17), createGetAttrNode('foo.bar')),
                    new BinaryNode('>', new ConstantNode(3), createGetAttrNode('foo.bar'))
                )
            ]
        };

        jasmine.itEachCase(cases, ast => {
            const actualAST = normalizeBoundaryConditionAST(ast);
            expect(actualAST).toEqual(
                new BinaryNode('and',
                    new BinaryNode('<', createGetAttrNode('foo.bar'), new ConstantNode(3)),
                    new BinaryNode('>', createGetAttrNode('foo.bar'), new ConstantNode(17))
                )
            );
        });
    });
});
