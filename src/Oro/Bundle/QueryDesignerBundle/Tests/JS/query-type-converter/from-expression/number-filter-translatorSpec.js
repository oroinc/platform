import NumberFilterTranstatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/number-filter-translator';
import FieldIdTranslator from 'oroquerydesigner/js/query-type-converter/from-expression/field-id-translator';
import {BinaryNode, ConstantNode, NameNode, tools} from 'oroexpressionlanguage/js/expression-language-library';

const {createArrayNode, createGetAttrNode} = tools;

describe('oroquerydesigner/js/query-type-converter/from-expression/number-filter-translator', () => {
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
                type: 'number',
                name: 'number',
                choices: [
                    {value: '1'},
                    {value: '2'},
                    {value: '3'},
                    {value: '4'},
                    {value: '5'},
                    {value: '6'},
                    {value: '7'},
                    {value: '8'},
                    {value: '9'},
                    {value: '10'},
                    {value: 'filter_empty_option'},
                    {value: 'filter_not_empty_option'}
                ]
            })
        ]);

        translator = new NumberFilterTranstatorFromExpression(
            new FieldIdTranslator(entityStructureDataProviderMock),
            filterConfigProviderMock
        );
    });

    describe('rejects node structure because', () => {
        const cases = {
            'improper AST':
                [new ConstantNode('mynumber')],
            'unsupported operation':
                [new BinaryNode('>=', createGetAttrNode('foo.bar'), createArrayNode([1, 2]))],
            'improper value in ArrayNode of right operand':
                [new BinaryNode('in', createArrayNode([new NameNode('foo'), 2]))],
            'improper value in ArrayNode of right operand with not indexed keys':
                [new BinaryNode('in', createArrayNode([1, '2b', 5, 6]))],
            'improper value in ArrayNode of right operand with nested arrays':
                [new BinaryNode('not in', createArrayNode([1, ['2b', 5], 5, 6]))]
        };

        jasmine.itEachCase(cases, ast => {
            expect(translator.tryToTranslate(ast)).toBe(null);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
        });
    });

    describe('can translate AST binary node', () => {
        const createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');

        const cases = {
            'when filter has `equals or greater than` type': [
                new BinaryNode('>=', createLeftOperand(), new ConstantNode(10)),
                {
                    type: '1',
                    value: 10
                }
            ],
            'when filter has `greater than` type': [
                new BinaryNode('>', createLeftOperand(), new ConstantNode(10)),
                {
                    type: '2',
                    value: 10
                }
            ],
            'when filter has `is equal to` type': [
                new BinaryNode('=', createLeftOperand(), new ConstantNode(10.5)),
                {
                    type: '3',
                    value: 10.5
                }
            ],
            'when filter has `is not equals to` type': [
                new BinaryNode('!=', createLeftOperand(), new ConstantNode(10)),
                {
                    type: '4',
                    value: 10
                }
            ],
            'when filter has `equals or less than` type': [
                new BinaryNode('<=', createLeftOperand(), new ConstantNode(10)),
                {
                    type: '5',
                    value: 10
                }
            ],
            'when filter has `less than` type': [
                new BinaryNode('<', createLeftOperand(), new ConstantNode(10)),
                {
                    type: '6',
                    value: 10
                }
            ],
            'when filter has `between` type': [
                new BinaryNode(
                    'and',
                    new BinaryNode('>=', createLeftOperand(), new ConstantNode(0)),
                    new BinaryNode('<=', createLeftOperand(), new ConstantNode(10))
                ),
                {
                    type: '7',
                    value: 0,
                    value_end: 10
                }
            ],
            'when filter has `not between` type': [
                new BinaryNode(
                    'and',
                    new BinaryNode('<', createLeftOperand(), new ConstantNode(1)),
                    new BinaryNode('>', createLeftOperand(), new ConstantNode(2))
                ),
                {
                    type: '8',
                    value: 1,
                    value_end: 2
                }
            ],
            'when filter has `range and values are string` type': [
                new BinaryNode(
                    'and',
                    new BinaryNode('>=', createLeftOperand(), new ConstantNode('10')),
                    new BinaryNode('<=', createLeftOperand(), new ConstantNode('1'))
                ),
                {
                    type: '7',
                    value: 10,
                    value_end: 1
                }
            ],
            'when filter has `is any of` type': [
                new BinaryNode('in', createLeftOperand(), createArrayNode([1, 2, '3'])),
                {
                    type: '9',
                    value: '1,2,3'
                }
            ],
            'when filter has `is not any of` type': [
                new BinaryNode('not in', createLeftOperand(), createArrayNode([1, 2, 3])),
                {
                    type: '10',
                    value: '1,2,3'
                }
            ],
            'when filter has `is empty` type': [
                new BinaryNode('=', createLeftOperand(), new ConstantNode(0)),
                {
                    type: 'filter_empty_option'
                }
            ],
            'when filter has `is not empty` type': [
                new BinaryNode('!=', createLeftOperand(), new ConstantNode(0)),
                {
                    type: 'filter_not_empty_option'
                }
            ]
        };

        jasmine.itEachCase(cases, (ast, filterValue) => {
            const expectedCondition = {
                columnName: 'bar',
                criterion: {
                    filter: 'number',
                    data: filterValue
                }
            };

            expect(translator.tryToTranslate(ast)).toEqual(expectedCondition);
        });
    });
});
