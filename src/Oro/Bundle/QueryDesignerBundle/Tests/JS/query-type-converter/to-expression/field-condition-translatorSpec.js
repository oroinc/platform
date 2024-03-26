import FieldConditionTranslatorToExpression
    from 'oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator';
import {BinaryNode, ConstantNode} from 'oroexpressionlanguage/js/expression-language-library';
import {createGetAttrNode} from 'oroexpressionlanguage/js/expression-language-tools';
import 'lib/jasmine-oro';

const createLeftOperand = () => createGetAttrNode('foo.bar');

describe('oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator', () => {
    let translator;
    let filterConfigProviderMock;
    let stringFilterTranslatorMock;
    let fieldIdTranslatorMock;
    const filterConfigs = {
        string: {
            type: 'string',
            name: 'string',
            choices: [
                {value: '1'},
                {value: '2'},
                {value: '3'}
            ]
        },
        number: {
            type: 'number',
            name: 'number',
            choices: [
                {value: '1'},
                {value: '2'},
                {value: '3'}
            ]
        }
    };

    beforeEach(() => {
        fieldIdTranslatorMock = jasmine.combineSpyObj('fieldIdTranslator', [
            jasmine.createSpy('translate').and.returnValue(createLeftOperand())
        ]);

        filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
            jasmine.createSpy('getFilterConfigByName').and.callFake(name => filterConfigs[name])
        ]);

        stringFilterTranslatorMock = jasmine.combineSpyObj('stringFilterTranslator', [
            jasmine.createSpy('test').and.returnValue(true),
            jasmine.createSpy('translate').and.returnValue(
                new BinaryNode('=', createLeftOperand(), new ConstantNode('baz'))
            )
        ]);

        const filterTranslatorProviderMock = jasmine.combineSpyObj('filterTranslatorProvider', [
            jasmine.createSpy('getTranslatorConstructor').and.callFake(name => {
                const filterTranslators = {
                    string: function StringFilterTranslator() {
                        return stringFilterTranslatorMock;
                    }
                };
                return filterTranslators[name] || null;
            })
        ]);

        translator = new FieldConditionTranslatorToExpression(
            fieldIdTranslatorMock,
            filterConfigProviderMock,
            filterTranslatorProviderMock
        );
    });

    it('does not call filter provider when condition structure is not valid', () => {
        translator.tryToTranslate({
            foo: 'bar'
        });
        expect(filterConfigProviderMock.getFilterConfigByName).not.toHaveBeenCalled();
    });

    it('calls filter provider\'s method `getFilterConfigsByName` with correct filter type', () => {
        translator.tryToTranslate({
            columnName: 'bar',
            criterion: {
                filter: 'string',
                data: {
                    type: '3',
                    value: 'baz'
                }
            }
        });
        expect(filterConfigProviderMock.getFilterConfigByName).toHaveBeenCalledWith('string');
    });

    it('calls filter translator\'s method `test` with filter value and config', () => {
        translator.tryToTranslate({
            columnName: 'bar',
            criterion: {
                filter: 'string',
                data: {
                    type: '3',
                    value: 'baz'
                }
            }
        });
        expect(stringFilterTranslatorMock.test).toHaveBeenCalledWith({
            type: '3',
            value: 'baz'
        });
    });

    it('calls field id translator\'s method `translate` with correct column name', () => {
        translator.tryToTranslate({
            columnName: 'bar',
            criterion: {
                filter: 'string',
                data: {
                    type: '3',
                    value: 'baz'
                }
            }
        });
        expect(fieldIdTranslatorMock.translate).toHaveBeenCalledWith('bar');
    });

    describe('can not translate condition because of', () => {
        const cases = {
            'invalid condition structure': [{
                foo: 'bar'
            }],
            'missing column name': [{
                criterion: {
                    filter: 'string',
                    data: {
                        type: '1',
                        value: 'baz'
                    }
                }
            }],
            'unknown filter type': [{
                columnName: 'baz',
                criterion: {
                    filter: 'oddnumber',
                    data: {
                        type: '3',
                        value: 7
                    }
                }
            }],
            'unavailable filter translator': [{
                columnName: 'baz',
                criterion: {
                    filter: 'number',
                    data: {
                        type: '3',
                        value: 7
                    }
                }
            }]
        };

        jasmine.itEachCase(cases, condition => {
            expect(translator.tryToTranslate(condition)).toBeNull();
        });
    });
});
