define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DictionaryFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/from-expression/dictionary-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/from-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

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
                    var arrayNode = new ArrayNode();
                    arrayNode.addElement(new ConstantNode(1));
                    arrayNode.addElement(new ConstantNode(2));
                    return new BinaryNode(
                        '>=',
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        arrayNode
                    );
                },
                'improper left operand AST': function() {
                    var arrayNode = new ArrayNode();
                    arrayNode.addElement(new ConstantNode(1));
                    arrayNode.addElement(new ConstantNode(2));
                    return new BinaryNode(
                        'in',
                        new ConstantNode('test'),
                        arrayNode
                    );
                },
                'improper right operand AST': function() {
                    return new BinaryNode(
                        'in',
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        new ConstantNode('test')
                    );
                },
                'improper AST values in right operand': function() {
                    var arrayNode = new ArrayNode();
                    arrayNode.addElement(new NameNode('foo'));
                    arrayNode.addElement(new ConstantNode(2));
                    return new BinaryNode(
                        'in',
                        new GetAttrNode(
                            new NameNode('foo'),
                            new ConstantNode('bar'),
                            new ArgumentsNode(),
                            GetAttrNode.PROPERTY_CALL
                        ),
                        arrayNode
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
                var arrayNode = new ArrayNode();
                arrayNode.addElement(new ConstantNode(1));
                arrayNode.addElement(new ConstantNode(2));
                node = new BinaryNode(
                    'in',
                    new GetAttrNode(
                        new NameNode('foo'),
                        new ConstantNode('bar'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    ),
                    arrayNode
                );
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
