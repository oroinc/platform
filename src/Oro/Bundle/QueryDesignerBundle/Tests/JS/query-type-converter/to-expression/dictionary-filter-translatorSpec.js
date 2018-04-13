define(function(require) {
    'use strict';

    var DictionaryFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator', function() {
        var translator;
        var filterConfigs = {
            'dictionary': {
                type: 'dictionary',
                name: 'dictionary',
                choices: [{value: '1'}, {value: '2'}]
            },
            'enum': {
                type: 'dictionary',
                name: 'enum',
                choices: [{value: '1'}, {value: '2'}]
            },
            'tag': {
                type: 'dictionary',
                name: 'tag',
                choices: [{value: '1'}, {value: '2'}, {value: '3'}]
            },
            'multicurrency': {
                type: 'dictionary',
                name: 'multicurrency',
                choices: [{value: '1'}, {value: '2'}]
            }
        };

        beforeEach(function() {
            translator = new DictionaryFilterTranslator();
        });

        describe('test filter value against filter config', function() {
            var cases = {
                'dictionary filter': [
                    {
                        type: '1',
                        value: ['3', '5', '6'],
                        params: {
                            'class': 'Oro\\Entity\\User'
                        }
                    },
                    filterConfigs['dictionary']
                ],
                'enum filter': [
                    {
                        type: '2',
                        value: ['expired', 'locked'],
                        params: {
                            'class': 'Extend\\Entity\\Status'
                        }
                    },
                    filterConfigs['enum']
                ],
                'tag filter': [
                    {
                        type: '3',
                        value: ['6', '5'],
                        params: {
                            'class': 'Oro\\Entity\\Tag',
                            'entityClass': 'Oro\\Entity\\User'
                        }
                    },
                    filterConfigs['tag']
                ],
                'multicurrency filter': [
                    {
                        type: '1',
                        value: ['UAH', 'EUR']
                    },
                    filterConfigs['multicurrency']
                ]
            };

            jasmine.itEachCase(cases, function(filterValue, filterConfig) {
                expect(translator.test(filterValue, filterConfig)).toBe(true);
            });
        });

        describe('can not translate filter value', function() {
            var cases = {
                'when unknown criterion type': [
                    {
                        type: '3',
                        value: ['1', '2']
                    },
                    filterConfigs['enum']
                ],
                'when invalid value': [
                    {
                        type: '1',
                        value: {1: 'UAH', 2: 'EUR'}
                    },
                    filterConfigs['multicurrency']
                ]
            };

            jasmine.itEachCase(cases, function(filterValue, filterConfig) {
                expect(translator.test(filterValue, filterConfig)).toBe(false);
            });
        });

        describe('translate filter value', function() {
            var createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');
            var cases = {
                'when filter has `is any of` filter value': [
                    {
                        type: '1',
                        value: ['UAH', 'EUR']
                    },
                    filterConfigs['multicurrency'],
                    new BinaryNode('in', createLeftOperand(), createArrayNode(['UAH', 'EUR']))
                ],
                'when filter has `is not any of` filter value': [
                    {
                        type: '2',
                        value: ['3', '5'],
                        params: {
                            'class': 'Oro\\Entity\\User'
                        }
                    },
                    filterConfigs['dictionary'],
                    new BinaryNode('not in', createLeftOperand(), createArrayNode(['3', '5']))
                ],
                'when filter has `equal` filter value': [
                    {
                        type: '3',
                        value: ['6', '5'],
                        params: {
                            'class': 'Oro\\Entity\\Tag',
                            'entityClass': 'Oro\\Entity\\User'
                        }
                    },
                    filterConfigs['tag'],
                    new BinaryNode('=', createLeftOperand(), createArrayNode(['6', '5']))
                ]
            };

            jasmine.itEachCase(cases, function(filterValue, filterConfig, expectedAST) {
                var leftOperand = createLeftOperand();

                expect(translator.test(filterValue, filterConfig)).toBe(true);
                expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
            });
        });
    });
});
