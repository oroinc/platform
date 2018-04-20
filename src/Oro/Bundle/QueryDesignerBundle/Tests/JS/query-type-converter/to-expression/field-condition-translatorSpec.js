define(function(require) {
    'use strict';

    var FieldConditionTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createLeftOperand = ExpressionLanguageLibrary.tools.createGetAttrNode.bind(null, 'foo.bar');

    describe('oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator', function() {
        var translator;
        var filterConfigProviderMock;
        var stringFilterTranslatorMock;
        var fieldIdTranslatorMock;
        var filterConfigs = {
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

        beforeEach(function() {
            fieldIdTranslatorMock = jasmine.combineSpyObj('fieldIdTranslator', [
                jasmine.createSpy('translate').and.returnValue(createLeftOperand())
            ]);

            filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('getFilterConfigByName').and.callFake(function(name) {
                    return filterConfigs[name];
                })
            ]);

            stringFilterTranslatorMock = jasmine.combineSpyObj('stringFilterTranslator', [
                jasmine.createSpy('test').and.returnValue(true),
                jasmine.createSpy('translate').and.returnValue(
                    new BinaryNode('=', createLeftOperand(), new ConstantNode('baz'))
                )
            ]);

            var filterTranslatorProviderMock = jasmine.combineSpyObj('filterTranslatorProvider', [
                jasmine.createSpy('getTranslator').and.callFake(function(name) {
                    var filterTranslators = {
                        string: function() {
                            return stringFilterTranslatorMock;
                        }
                    };
                    return filterTranslators[name] || null;
                })
            ]);

            translator = new FieldConditionTranslator(
                fieldIdTranslatorMock,
                filterConfigProviderMock,
                filterTranslatorProviderMock
            );
        });

        it('does not call filter provider when condition structure is not valid', function() {
            translator.tryToTranslate({
                foo: 'bar'
            });
            expect(filterConfigProviderMock.getFilterConfigByName).not.toHaveBeenCalled();
        });

        it('calls filter provider\'s method `getFilterConfigsByName` with correct filter type', function() {
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

        it('calls filter translator\'s method `test` with filter value and config', function() {
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

        it('calls field id translator\'s method `translate` with correct column name', function() {
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

        it('translates condition to appropriate AST', function() {
            var result = translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'string',
                    data: {
                        type: '3',
                        value: 'baz'
                    }
                }
            });
            var expectedAST = new BinaryNode('=', createLeftOperand(), new ConstantNode('baz'));

            expect(result).toEqual(expectedAST);
        });

        describe('can not translate condition because of', function() {
            var cases = {
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

            jasmine.itEachCase(cases, function(condition) {
                expect(translator.tryToTranslate(condition)).toBeNull();
            });
        });
    });
});
