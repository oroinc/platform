define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DictionaryFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator');
    var FieldIdTranslator = require('oroquerydesigner/js/query-type-converter/to-expression/field-id-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var ArrayNode = ExpressionLanguageLibrary.ArrayNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator', function() {
        var translator;

        beforeEach(function() {
            var entityStructureDataProviderMock = jasmine.combineSpyObj('entityStructureDataProvider', [
                jasmine.createSpy('getRelativePropertyPathByPath').and.returnValue('bar'),
                jasmine.combineSpyObj('rootEntity', [
                    jasmine.createSpy('get').and.returnValue('foo')
                ])
            ]);

            var filterConfigProviderMock = jasmine.combineSpyObj('filterConfigProvider', [
                jasmine.createSpy('getFilterConfigsByType').and.returnValue([
                    {type: 'dictionary', name: 'dictionary', choices: [{value: '1'}, {value: '2'}]},
                    {type: 'dictionary', name: 'enum', choices: [{value: '1'}, {value: '2'}]},
                    {type: 'dictionary', name: 'tag', choices: [{value: '1'}, {value: '2'}, {value: '3'}]},
                    {type: 'dictionary', name: 'multicurrency', choices: [{value: '1'}, {value: '2'}]}
                ])
            ]);

            translator = new DictionaryFilterTranslator(
                new FieldIdTranslator(entityStructureDataProviderMock),
                filterConfigProviderMock
            );
        });

        describe('test valid conditions', function() {
            var cases = {
                'dictionary filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'dictionary',
                        data: {
                            type: '1',
                            value: ['3', '5', '6'],
                            params: {
                                'class': 'Oro\\Entity\\User'
                            }
                        }
                    }
                },
                'enum filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'enum',
                        data: {
                            type: '2',
                            value: ['expired', 'locked'],
                            params: {
                                'class': 'Extend\\Entity\\Status'
                            }
                        }
                    }
                },
                'tag filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'tag',
                        data: {
                            type: '3',
                            value: ['6', '5']
                        },
                        params: {
                            'class': 'Oro\\Entity\\Tag',
                            'entityClass': 'Oro\\Entity\\User'
                        }
                    }
                },
                'multicurrency filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'multicurrency',
                        data: {
                            type: '1',
                            value: ['UAH', 'EUR']
                        }
                    }
                }
            };

            _.each(cases, function(condition, caseName) {
                it(caseName, function() {
                    expect(translator.tryToTranslate(condition)).not.toBe(null);
                });
            });
        });

        describe('test invalid conditions', function() {
            var cases = {
                'unknown filter': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'mydictionary',
                        data: {
                            type: '1',
                            value: ['3', '5', '6']
                        }
                    }
                },
                'unknown criterion type': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'enum',
                        data: {
                            type: '3',
                            value: ['1', '2']
                        }
                    }
                },
                'missing column name': {
                    criterion: {
                        filter: 'tag',
                        data: {
                            type: '3',
                            value: ['6', '5']
                        }
                    }
                },
                'invalid value': {
                    columnName: 'bar',
                    criterion: {
                        filter: 'multicurrency',
                        data: {
                            type: '1',
                            value: {1: 'UAH', 2: 'EUR'}
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

        it('translate `is any of` condition', function() {
            var actual = translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'multicurrency',
                    data: {
                        type: '1',
                        value: ['UAH', 'EUR']
                    }
                }
            });

            var arrayNode = new ArrayNode();
            arrayNode.addElement(new ConstantNode('UAH'));
            arrayNode.addElement(new ConstantNode('EUR'));

            var expected = new BinaryNode(
                'in',
                new GetAttrNode(
                    new NameNode('foo'), new ConstantNode('bar'), new ArgumentsNode(), GetAttrNode.PROPERTY_CALL),
                arrayNode
            );

            expect(actual).toEqual(expected);
        });

        it('translate `is not any of` condition', function() {
            var actual = translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'dictionary',
                    data: {
                        type: '2',
                        value: ['3', '5'],
                        params: {
                            'class': 'Oro\\Entity\\User'
                        }
                    }
                }
            });

            var arrayNode = new ArrayNode();
            arrayNode.addElement(new ConstantNode('3'));
            arrayNode.addElement(new ConstantNode('5'));

            var expected = new BinaryNode(
                'not in',
                new GetAttrNode(
                    new NameNode('foo'), new ConstantNode('bar'), new ArgumentsNode(), GetAttrNode.PROPERTY_CALL),
                arrayNode
            );

            expect(actual).toEqual(expected);
        });

        it('translate `equal` condition', function() {
            var actual = translator.tryToTranslate({
                columnName: 'bar',
                criterion: {
                    filter: 'tag',
                    data: {
                        type: '3',
                        value: ['6', '5']
                    },
                    params: {
                        'class': 'Oro\\Entity\\Tag',
                        'entityClass': 'Oro\\Entity\\User'
                    }
                }
            });

            var arrayNode = new ArrayNode();
            arrayNode.addElement(new ConstantNode('6'));
            arrayNode.addElement(new ConstantNode('5'));

            var expected = new BinaryNode(
                '=',
                new GetAttrNode(
                    new NameNode('foo'), new ConstantNode('bar'), new ArgumentsNode(), GetAttrNode.PROPERTY_CALL),
                arrayNode
            );

            expect(actual).toEqual(expected);
        });
    });
});
