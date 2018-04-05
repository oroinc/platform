define(function(require) {
    'use strict';

    var _ = require('underscore');
    var exposure = require('requirejs-exposure')
        .disclose('oroquerydesigner/js/query-type-converter/to-expression/abstract-condition-translator');
    var FieldConditionTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/field-condition-translator', function() {
        var translator;
        var filterConfigProviderMock;
        var stringFilterTranslatorMock;
        var fieldIdTranslatorMock;

        function createGetFieldAST() {
            return new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('bar'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
        }

        function getFilterConfig() {
            return {
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
            };
        }

        beforeEach(function() {
            fieldIdTranslatorMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('translate').and.returnValue(createGetFieldAST())
            ]);

            filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('getFilterConfigByName').and.returnValue(getFilterConfig())
            ]);

            stringFilterTranslatorMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('test').and.returnValue(true),
                jasmine.createSpy('translate').and.returnValue(new BinaryNode(
                    '=',
                    createGetFieldAST(),
                    new ConstantNode('baz')
                ))
            ]);

            exposure.substitute('StringFilterTranslator').by(function() {
                return stringFilterTranslatorMock;
            });

            translator = new FieldConditionTranslator(fieldIdTranslatorMock, filterConfigProviderMock);
        });

        afterEach(function() {
            exposure.recover('StringFilterTranslator');
        });

        it('doesn\'t call filter provider when condition structure doesn\'t fit', function() {
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
            expect(stringFilterTranslatorMock.test).toHaveBeenCalledWith(
                {
                    type: '3',
                    value: 'baz'
                },
                getFilterConfig()
            );
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
            var resultAST = translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'string',
                    data: {
                        type: '3',
                        value: 'baz'
                    }
                }
            });
            var expectedAST = new BinaryNode(
                '=',
                createGetFieldAST(),
                new ConstantNode('baz')
            );

            expect(resultAST).toEqual(expectedAST);
        });

        describe('can\'t translate condition because of', function() {
            var cases = {
                'unknown condition structure': {
                    foo: 'bar'
                },
                'missing column name': {
                    criterion: {
                        filter: 'string',
                        data: {
                            type: '1',
                            value: 'baz'
                        }
                    }
                }
            };

            _.each(cases, function(condition, caseName) {
                it(caseName, function() {
                    expect(translator.tryToTranslate(condition)).toBeNull();
                });
            });
        });
    });
});
