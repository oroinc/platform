define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DictionaryFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator', function() {
        var translator;
        var entityStructureDataProviderMock;
        var filterConfigProviderMock;

        beforeEach(function() {
            entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getPathByRelativePropertyPath').and.returnValue('bar'),
                jasmine.createSpy('getFieldSignatureSafely'),
                jasmine.combineSpyObj('rootEntity', [
                    jasmine.createSpy('get').and.returnValue('foo')
                ])
            ]);
            filterConfigProviderMock = jasmine.createSpyObj('filterConfigProvider', ['getApplicableFilterConfig']);

            translator = new DictionaryFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });


        describe('check node structure', function() {
            var cases = {
                'improper AST': function() {
                    return new ConstantNode('test');
                },
                'improper operation': function() {
                    return new BinaryNode('>=', createGetAttrNode('foo.bar'), createArrayNode([1, 2]));
                },
                'improper left operand AST': function() {
                    return new BinaryNode(
                        'in',
                        new ConstantNode('test'),
                        createArrayNode([1, 2])
                    );
                },
                'improper right operand AST': function() {
                    return new BinaryNode('in', createGetAttrNode('foo.bar'), new ConstantNode('test'));
                },
                'improper AST values in right operand': function() {
                    return new BinaryNode(
                        'in',
                        createGetAttrNode('foo.bar'),
                        createArrayNode([new NameNode('foo'), 2])
                    );
                }
            };

            _.each(cases, function(cb, caseName) {
                it(caseName, function() {
                    var node = cb();
                    expect(translator.tryToTranslate(node)).toBe(null);
                    expect(entityStructureDataProviderMock.getFieldSignatureSafely).not.toHaveBeenCalled();
                });
            });
        });

        describe('valid node structure', function() {
            var node;

            beforeEach(function() {
                node = new BinaryNode('in', createGetAttrNode('foo.bar'), createArrayNode(['1', '2']));
            });

            var cases = {
                'improper filter type': {
                    filterConfig: {type: 'number'},
                    expected: null
                },
                'unavailable operation in filter config': {
                    filterConfig: {
                        type: 'dictionary',
                        name: 'enum',
                        choices: [{value: '2'}, {value: '3'}]
                    },
                    expected: null
                },
                'successful translation': {
                    filterConfig: {
                        type: 'dictionary',
                        name: 'enum',
                        choices: [{value: '1'}, {value: '2'}]
                    },
                    expected: {
                        columnName: 'bar',
                        criterion: {
                            filter: 'enum',
                            data: {
                                type: '1',
                                value: ['1', '2']
                            }
                        }
                    }
                },
                'successful translation with extra params': {
                    filterConfig: {
                        type: 'dictionary',
                        name: 'tag',
                        choices: [{value: '1'}, {value: '2'}],
                        filterParams: {'class': 'Oro\\TagClass', 'entityClass': 'Oro\\BarClass'}
                    },
                    expected: {
                        columnName: 'bar',
                        criterion: {
                            filter: 'tag',
                            data: {
                                type: '1',
                                value: ['1', '2'],
                                params: {'class': 'Oro\\TagClass', 'entityClass': 'Oro\\BarClass'}
                            }
                        }
                    }
                }
            };

            _.each(cases, function(caseData, caseName) {
                it(caseName, function() {
                    filterConfigProviderMock.getApplicableFilterConfig.and.returnValue(caseData.filterConfig);
                    expect(translator.tryToTranslate(node)).toEqual(caseData.expected);
                    expect(entityStructureDataProviderMock.getFieldSignatureSafely).toHaveBeenCalledWith('bar');
                });
            });
        });
    });
});
