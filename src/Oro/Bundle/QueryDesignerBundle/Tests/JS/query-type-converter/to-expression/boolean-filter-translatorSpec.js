define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BooleanFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var ArgumentsNode = ExpressionLanguageLibrary.ArgumentsNode;
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var GetAttrNode = ExpressionLanguageLibrary.GetAttrNode;
    var NameNode = ExpressionLanguageLibrary.NameNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/boolean-filter-translator', function() {
        var translator;
        var filterConfig;

        beforeEach(function() {
            translator = new BooleanFilterTranslator();

            filterConfig = {
                type: 'boolean',
                name: 'boolean',
                choices: [
                    {value: '1'},
                    {value: '2'}
                ]
            };
        });

        it('can\'t translate condition because of incorrect value type', function() {
            expect(translator.test({value: ['1', '2']}, filterConfig)).toBe(false);
        });

        it('can\'t translate condition because of missing value', function() {
            expect(translator.test({type: '1'}, filterConfig)).toBe(false);
        });

        describe('can translate filterValue', function() {
            var cases = {
                yes: [
                    {value: '1'},
                    new ConstantNode(true)
                ],
                no: [
                    {value: '2'},
                    new ConstantNode(false)
                ]
            };

            _.each(cases, function(testCase, caseName) {
                it('when field value is `' + caseName +'`', function() {
                    var leftOperand = new GetAttrNode(
                        new NameNode('foo'),
                        new ConstantNode('bar'),
                        new ArgumentsNode(),
                        GetAttrNode.PROPERTY_CALL
                    );
                    var expectedAST = new BinaryNode('==', leftOperand, testCase[1]);

                    expect(translator.test(testCase[0], filterConfig)).toBe(true);
                    expect(translator.translate(leftOperand, testCase[0])).toEqual(expectedAST);
                });
            });
        });
    });
});
