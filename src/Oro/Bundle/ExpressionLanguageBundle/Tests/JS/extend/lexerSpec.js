define(function(require) {
    'use strict';

    var Lexer = require('oroexpressionlanguage/js/extend/lexer');
    var Token = require('oroexpressionlanguage/js/library/token');
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
                    new Token(Token.OPERATOR_TYPE, '==', 3),
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
    });
});
