define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DictionaryFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/dictionary-filter-translator', function() {
        var translator;
        var filterConfigs = null;

        function createGetFieldAST() {
            return new GetAttrNode(
                new NameNode('foo'),
                new ConstantNode('bar'),
                new ArgumentsNode(),
                GetAttrNode.PROPERTY_CALL
            );
        }

        beforeEach(function() {
            filterConfigs = {
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

            translator = new DictionaryFilterTranslator();
        });

        describe('test valid conditions', function() {
            var cases = {
                'dictionary filter': [
                    'dictionary',
                    {
                        type: '1',
                        value: ['3', '5', '6'],
                        params: {
                            'class': 'Oro\\Entity\\User'
                        }
                    }
                ],
                'enum filter': [
                    'enum',
                    {
                        type: '2',
                        value: ['expired', 'locked'],
                        params: {
                            'class': 'Extend\\Entity\\Status'
                        }
                    }
                ],
                'tag filter': [
                    'tag',
                    {
                        type: '3',
                        value: ['6', '5'],
                        params: {
                            'class': 'Oro\\Entity\\Tag',
                            'entityClass': 'Oro\\Entity\\User'
                        }
                    }
                ],
                'multicurrency filter': [
                    'multicurrency',
                    {
                        type: '1',
                        value: ['UAH', 'EUR']
                    }
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    var filterConfig = filterConfigs[testCase[0]];

                    expect(translator.test(testCase[1], filterConfig)).toBe(true);
                });
            });
        });

        describe('test invalid conditions', function() {
            var cases = {
                'unknown criterion type': [
                    'enum',
                    {
                        type: '3',
                        value: ['1', '2']
                    }
                ],
                'invalid value': [
                    'multicurrency',
                    {
                        type: '1',
                        value: {1: 'UAH', 2: 'EUR'}
                    }
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    var filterConfig = filterConfigs[testCase[0]];

                    expect(translator.test(testCase[1], filterConfig)).toBe(false);
                });
            });
        });

        describe('test invalid conditions', function() {
            var cases = {
                'translate `is any of` condition': [
                    'multicurrency',
                    {
                        type: '1',
                        value: ['UAH', 'EUR']
                    },
                    'in',
                    createArrayNode(['UAH', 'EUR'])
                ],
                'translate `is not any of` condition': [
                    'dictionary',
                    {
                        type: '2',
                        value: ['3', '5'],
                        params: {
                            'class': 'Oro\\Entity\\User'
                        }
                    },
                    'not in',
                    createArrayNode(['3', '5'])
                ],
                'translate `equal` condition': [
                    'tag',
                    {
                        type: '3',
                        value: ['6', '5'],
                        params: {
                            'class': 'Oro\\Entity\\Tag',
                            'entityClass': 'Oro\\Entity\\User'
                        }
                    },
                    '=',
                    createArrayNode(['6', '5'])
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it(caseName, function() {
                    var filterConfig = filterConfigs[testCase[0]];
                    var expectedAST = new BinaryNode(
                        testCase[2],
                        createGetFieldAST(),
                        testCase[3]
                    );

                    expect(translator.test(testCase[1], filterConfig)).toBe(true);
                    expect(translator.translate(createGetFieldAST(), testCase[1])).toEqual(expectedAST);
                });
            });
        });
    });
});
