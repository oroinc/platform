define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BooleanFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator', function() {
        var translator;
        var filterConfigProviderMock;

        beforeEach(function() {
            var entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getRelativePropertyPathByPath').and.returnValue('bar'),
                jasmine.combineSpyObj('rootEntity', [
                    jasmine.createSpy('get').and.returnValue('foo')
                ])
            ]);

            filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('getFilterConfigsByType').and.returnValue([
                    {
                        type: 'boolean',
                        name: 'boolean',
                        choices: [
                            {value: '1'},
                            {value: '2'}
                        ]
                    }
                ])
            ]);

            translator = new BooleanFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });

        it('calls filter provider\'s method `getFilterConfigsByType` with correct filter type', function() {
            translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'boolean',
                    data: {
                        value: '1'
                    }
                }
            });
            expect(filterConfigProviderMock.getFilterConfigsByType).toHaveBeenCalledWith('boolean');
        });

        describe('test valid conditions', function() {
            var cases = {
                yes: [
                    {
                        value: '1'
                    },
                    new BinaryNode(
                        '==',
                        createGetAttrNode('foo.bar'),
                        new ConstantNode(true)
                    )
                ],
                no: [
                    {
                        value: '2'
                    },
                    new BinaryNode(
                        '==',
                        createGetAttrNode('foo.bar'),
                        new ConstantNode(false)
                    )
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it('When field value is `' + caseName +'`', function() {
                    var condition = {
                        columnName: 'bar',
                        criterion: {
                            filter: 'boolean',
                            data: testCase[0]
                        }
                    };

                    expect(translator.tryToTranslate(condition)).toEqual(testCase[1]);
                });
            });
        });

        describe('can\'t translate condition because of', function() {
            var cases = {
                'unknown filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'myboolean',
                        data: {
                            value: '1'
                        }
                    }
                },
                'missing column name': {
                    criterion: {
                        filter: 'boolean',
                        data: {
                            value: '1'
                        }
                    }
                },
                'missing value': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'boolean',
                        data: {}
                    }
                },
                'incorrect value type': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'boolean',
                        data: {
                            value: 1
                        }
                    }
                }
            };

            _.each(cases, function(condition, caseName) {
                it(caseName, function() {
                    expect(translator.tryToTranslate(condition)).toBe(null);
                });
            });
        });
    });
});
