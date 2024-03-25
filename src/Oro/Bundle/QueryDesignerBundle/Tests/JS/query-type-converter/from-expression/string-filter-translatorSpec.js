import StringFilterTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator';
import FieldIdTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/field-id-translator';
import {BinaryNode, ConstantNode, NameNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createArrayNode, createFunctionNode, createGetAttrNode}
    from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/from-expression/string-filter-translator', () => {
    let translator;
    let entityStructureDataProviderMock;
    let filterConfigProviderMock;

    beforeEach(() => {
        entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
            jasmine.createSpy('getPathByRelativePropertyPath').and.returnValue('bar'),
            jasmine.createSpy('getFieldSignatureSafely'),
            jasmine.combineSpyObj('rootEntity', [
                jasmine.createSpy('get').and.returnValue('foo')
            ])
        ]);

        filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
            jasmine.createSpy('getApplicableFilterConfig').and.returnValue({
                type: 'string',
                name: 'string',
                choices: [
                    {value: '1'},
                    {value: '2'},
                    {value: '3'},
                    {value: '4'},
                    {value: '5'},
                    {value: '6'},
                    {value: '7'},
                    {value: 'filter_empty_option'},
                    {value: 'filter_not_empty_option'}
                ]
            })
        ]);

        translator = new StringFilterTranslatorFromExpression(
            new FieldIdTranslatorFromExpression(entityStructureDataProviderMock),
            filterConfigProviderMock
        );
    });

    describe('rejects node structure because', () => {
        const cases = {
            'unsupported operation': [
                '>=',
                new ConstantNode('baz')
            ],
            'improper right operand AST': [
                '=',
                new NameNode('baz')
            ],
            'operation `in` expects ArrayNode as right operand': [
                'in',
                new ConstantNode('baz')
            ],
            'operation `=` expects ConstantNode as right operand': [
                '=',
                createArrayNode(['baz'])
            ],
            'improper value in ArrayNode of right operand': [
                'in',
                createArrayNode([new NameNode('foo'), 2])
            ],
            'improper type of ArrayNode of right operand': [
                'in',
                createArrayNode({foo: 1})
            ],
            'operation `matches` expects FunctionNode as right operand': [
                'matches',
                new ConstantNode('baz')
            ],
            'improper value modifier for `matches` operator': [
                'matches',
                createFunctionNode('foo')
            ],
            'value modifier expects to have an argument': [
                'matches',
                createFunctionNode('containsRegExp')
            ],
            'value modifier expects to have only argument': [
                'matches',
                createFunctionNode('containsRegExp', ['foo', 'bar'])
            ],
            'value modifier expects ConstantNode as argument': [
                'matches',
                createFunctionNode('containsRegExp', [createArrayNode(['foo'])])
            ]
        };

        jasmine.itEachCase(cases, (operator, rightNode) => {
            const node = new BinaryNode(operator, createGetAttrNode('foo.bar'), rightNode);
            expect(translator.tryToTranslate(node)).toBe(null);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
        });

        it('improper AST node type', () => {
            expect(translator.tryToTranslate(new ConstantNode('foo'))).toBe(null);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
        });
    });

    describe('can translate AST binary node', () => {
        const cases = {
            'having `matches` operator with `containsRegExp` value modifier': [
                // BinaryNode operator
                'matches',

                // BinaryNode right operand
                createFunctionNode('containsRegExp', ['baz']),

                // expected condition filter data
                {
                    type: '1',
                    value: 'baz'
                }
            ],
            'having `not matches` operator with `containsRegExp` value modifier': [
                'not matches',
                createFunctionNode('containsRegExp', ['baz']),
                {
                    type: '2',
                    value: 'baz'
                }
            ],
            'having `=` operator with string value': [
                '=',
                new ConstantNode('baz'),
                {
                    type: '3',
                    value: 'baz'
                }
            ],
            'having `matches` operator with `startWithRegExp` value modifier': [
                'matches',
                createFunctionNode('startWithRegExp', ['baz']),
                {
                    type: '4',
                    value: 'baz'
                }
            ],
            'having `matches` operator with `endWithRegExp` value modifier': [
                'matches',
                createFunctionNode('endWithRegExp', ['baz']),
                {
                    type: '5',
                    value: 'baz'
                }
            ],
            'having `in` operator with array of string': [
                'in',
                createArrayNode(['baz', 'qux']),
                {
                    type: '6',
                    value: 'baz, qux'
                }
            ],
            'having `not in` operator with array of string': [
                'not in',
                createArrayNode(['baz', 'qux']),
                {
                    type: '7',
                    value: 'baz, qux'
                }
            ],
            'having `=` operator with empty string': [
                '=',
                new ConstantNode(''),
                {
                    type: 'filter_empty_option',
                    value: ''
                }
            ],
            'having `!=` operator with empty string': [
                '!=',
                new ConstantNode(''),
                {
                    type: 'filter_not_empty_option',
                    value: ''
                }
            ]
        };

        jasmine.itEachCase(cases, (operator, rightNode, filterValue) => {
            const node = new BinaryNode(operator, createGetAttrNode('foo.bar'), rightNode);
            const expectedCondition = {
                columnName: 'bar',
                criterion: {
                    filter: 'string',
                    data: filterValue
                }
            };

            expect(translator.tryToTranslate(node)).toEqual(expectedCondition);
        });
    });
});
