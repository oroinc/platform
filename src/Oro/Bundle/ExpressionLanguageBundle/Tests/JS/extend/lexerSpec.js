define(function(require) {
    'use strict';

    var Lexer = require('oroexpressionlanguage/js/extend/lexer');
    var Token = require('oroexpressionlanguage/js/extend/token');
    var TokenStream = require('oroexpressionlanguage/js/library/token-stream');

    describe('oroexpressionlanguage/js/extend/lexer', function() {
        var lexer;

        beforeEach(function() {
            lexer = new Lexer();
        });

        describe('valid expressions', function() {
            var cases = [
                ['3 = 5', [
                    new Token(Token.NUMBER_TYPE, 3, 1),
                    new Token(Token.OPERATOR_TYPE, '=', 3),
                    new Token(Token.NUMBER_TYPE, 5, 5)
                ]],
                ['3 == 5', [
                    new Token(Token.NUMBER_TYPE, 3, 1),
                    new Token(Token.OPERATOR_TYPE, '==', 3, 2),
                    new Token(Token.NUMBER_TYPE, 5, 6)
                ]]
            ];

            cases.forEach(function(testCase) {
                it(testCase[0], function() {
                    var expression = testCase[0];
                    var tokens = testCase[1].concat([
                        new Token(Token.EOF_TYPE, null, expression.length + 1)
                    ]);
                    expect(lexer.tokenize(expression)).toEqual(new TokenStream(tokens, expression));
                });
            });
        });

        describe('invalid expressions with tokenizeForce', function() {
            var cases = [
                ['3 = # 5', [
                    new Token(Token.NUMBER_TYPE, 3, 1),
                    new Token(Token.OPERATOR_TYPE, '=', 3),
                    new Token(Token.ERROR_TYPE, 'Unexpected character "#"', 5),
                    new Token(Token.NUMBER_TYPE, 5, 7)
                ]],
                ['(3 == 5', [
                    new Token(Token.PUNCTUATION_TYPE, '(', 1),
                    new Token(Token.NUMBER_TYPE, 3, 2),
                    new Token(Token.OPERATOR_TYPE, '==', 4, 2),
                    new Token(Token.NUMBER_TYPE, 5, 7)
                ]],
                ['3 == ]5', [
                    new Token(Token.NUMBER_TYPE, 3, 1),
                    new Token(Token.OPERATOR_TYPE, '==', 3, 2),
                    new Token(Token.ERROR_TYPE, 'Unexpected "]"', 6),
                    new Token(Token.NUMBER_TYPE, 5, 7)
                ]],
                ['(3 + [ 5) ]', [
                    new Token(Token.PUNCTUATION_TYPE, '(', 1),
                    new Token(Token.NUMBER_TYPE, 3, 2),
                    new Token(Token.OPERATOR_TYPE, '+', 4),
                    new Token(Token.PUNCTUATION_TYPE, '[', 6),
                    new Token(Token.NUMBER_TYPE, 5, 8),
                    new Token(Token.ERROR_TYPE, 'Unclosed "["', 9),
                    new Token(Token.ERROR_TYPE, 'Unclosed "("', 11)
                ]]
            ];

            cases.forEach(function(testCase) {
                it(testCase[0], function() {
                    var expression = testCase[0];
                    var tokens = testCase[1].concat([
                        new Token(Token.EOF_TYPE, null, expression.length + 1)
                    ]);
                    expect(lexer.tokenizeForce(expression)).toEqual(new TokenStream(tokens, expression));
                });
            });
        });
    });
});
