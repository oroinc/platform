import BooleanFilterTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/boolean-filter-translator';
import FieldIdTranslatorFromExpression
    from 'oroquerydesigner/js/query-type-converter/from-expression/field-id-translator';
import {ArgumentsNode, BinaryNode, ConstantNode, GetAttrNode, NameNode, UnaryNode}
    from 'oroexpressionlanguage/js/expression-language-library';
import 'lib/jasmine-oro';

describe('oroquerydesigner/js/query-type-converter/from-expression/boolean-filter-translator', () => {
    let translator;
    let entityStructureDataProviderMock;
    let filterConfigProviderMock;
    const createGetAttrNode = () =>
        new GetAttrNode(new NameNode('foo'), new ConstantNode('bar'), new ArgumentsNode(), GetAttrNode.PROPERTY_CALL);
    const createBinaryNode = (operator, value) =>
        new BinaryNode(operator, createGetAttrNode(), new ConstantNode(value));
    const createUnaryNode = operator => new UnaryNode(operator, createGetAttrNode());

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
                type: 'boolean',
                name: 'boolean',
                choices: [
                    {value: '1'},
                    {value: '2'}
                ]
            })
        ]);

        translator = new BooleanFilterTranslatorFromExpression(
            new FieldIdTranslatorFromExpression(entityStructureDataProviderMock),
            filterConfigProviderMock
        );
    });

    describe('Check filterConfig', () => {
        const cases = {
            'incorrect filter type': [{
                type: 'meboolean',
                name: 'meboolean',
                choices: [{value: '1'}, {value: '2'}]
            }],
            'unavailable operation in filter config': [{
                type: 'boolean',
                name: 'boolean',
                choices: [{value: '0'}, {value: '3'}]
            }]
        };

        jasmine.itEachCase(cases, filterConfig => {
            const node = createBinaryNode('==', false);

            filterConfigProviderMock.getApplicableFilterConfig.and.returnValue(filterConfig);
            expect(translator.tryToTranslate(node)).toEqual(null);
        });
    });

    describe('check invalid structure', () => {
        const cases = {
            'short expression with incorrect operator': [createUnaryNode('+')],
            'long expression with incorrect operator': [createBinaryNode('!=', false)],
            'long expression with incorrect value type': [createBinaryNode('==', 'false')]
        };

        jasmine.itEachCase(cases, ast => {
            expect(translator.tryToTranslate(ast)).toBe(null);
            expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
        });
    });

    describe('check valid structure', () => {
        const cases = {
            'expression with binary operator `==` and value is `false`': [
                createBinaryNode('==', false),
                {value: '2'}
            ],
            'expression with binary operator `==` and value is `true`': [
                createBinaryNode('==', true),
                {value: '1'}
            ],
            'expression with binary operator `=` and value is `false`': [
                createBinaryNode('=', false),
                {value: '2'}
            ],
            'expression with binary operator `=` and value is `true`': [
                createBinaryNode('=', true),
                {value: '1'}
            ],
            'expression with unary operator `!`': [
                createUnaryNode('!'),
                {value: '2'}
            ],
            'expression with unary operator `not`': [
                createUnaryNode('not'),
                {value: '2'}
            ],
            'expression without operator': [
                createGetAttrNode(),
                {value: '1'}
            ]
        };

        jasmine.itEachCase(cases, (ast, filterValue)=> {
            const expectedCondition = {
                columnName: 'bar',
                criterion: {
                    filter: 'boolean',
                    data: filterValue
                }
            };
            expect(translator.tryToTranslate(ast)).toEqual(expectedCondition);
        });
    });
});
