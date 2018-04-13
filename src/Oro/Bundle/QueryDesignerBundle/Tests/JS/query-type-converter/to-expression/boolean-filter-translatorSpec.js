define(function(require) {
    'use strict';

    var BooleanFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator', function() {
        var translator;
        var filterConfig = {
            type: 'boolean',
            name: 'boolean',
            choices: [
                {value: '1'},
                {value: '2'}
            ]
        };

        beforeEach(function() {
            translator = new BooleanFilterTranslator(filterConfig);
        });

        describe('can not translate filter value', function() {
            var cases = {
                'when incorrect value type': [
                    {value: ['1', '2']}
                ],
                'when unknown value': [
                    {value: '3'}
                ]
            };

            jasmine.itEachCase(cases, function(filterValue) {
                expect(translator.test(filterValue)).toBe(false);
            });
        });

        describe('translate filter value', function() {
            var createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');
            var cases = {
                'when field value is `yes`': [
                    {value: '1'},
                    new BinaryNode('=', createLeftOperand(), new ConstantNode(true))
                ],
                'when field value is `no`': [
                    {value: '2'},
                    new BinaryNode('=', createLeftOperand(), new ConstantNode(false))
                ]
            };

            jasmine.itEachCase(cases, function(filterValue, expectedAST) {
                var leftOperand = createLeftOperand();

                expect(translator.test(filterValue)).toBe(true);
                expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
            });
        });
    });
});
