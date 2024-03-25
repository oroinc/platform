import Lexer from 'oroexpressionlanguage/js/library/lexer';
import Token from 'oroexpressionlanguage/js/library/token';
import TokenStream from 'oroexpressionlanguage/js/library/token-stream';

describe('oroexpressionlanguage/js/library/lexer', () => {
    let lexer;
    beforeEach(() => {
        lexer = new Lexer();
    });

    describe('invalid expressions', () => {
        const cases = [
            [
                'Unexpected character "\'"',
                'around position 33 for expression',
                'service(faulty.expression.example\').dummyMethod()'
            ],

            [
                'Unclosed "("',
                'around position 7 for expression',
                'service(unclosed.expression.dummyMethod()'
            ]
        ];

        cases.forEach(testCase => {
            it(testCase[0].toLowerCase(), () => {
                expect(() => {
                    lexer.tokenize(testCase[2]);
                }).toThrowError(testCase[0] + ' ' + testCase[1] + ' `' + testCase[2] + '`.');
            });
        });
    });

    describe('valid expressions', () => {
        const cases = [
            ['  a  ', [new Token(Token.NAME_TYPE, 'a', 3)]],
            ['a', [new Token(Token.NAME_TYPE, 'a', 1)]],
            ['"foo"', [new Token(Token.STRING_TYPE, 'foo', 1)]],
            ['3', [new Token(Token.NUMBER_TYPE, 3, 1)]],
            ['+', [new Token(Token.OPERATOR_TYPE, '+', 1)]],
            ['.', [new Token(Token.PUNCTUATION_TYPE, '.', 1)]],
            [
                '(3 + 5) ~ foo("bar").baz[4]',
                [
                    new Token(Token.PUNCTUATION_TYPE, '(', 1),
                    new Token(Token.NUMBER_TYPE, 3, 2),
                    new Token(Token.OPERATOR_TYPE, '+', 4),
                    new Token(Token.NUMBER_TYPE, 5, 6),
                    new Token(Token.PUNCTUATION_TYPE, ')', 7),
                    new Token(Token.OPERATOR_TYPE, '~', 9),
                    new Token(Token.NAME_TYPE, 'foo', 11),
                    new Token(Token.PUNCTUATION_TYPE, '(', 14),
                    new Token(Token.STRING_TYPE, 'bar', 15),
                    new Token(Token.PUNCTUATION_TYPE, ')', 20),
                    new Token(Token.PUNCTUATION_TYPE, '.', 21),
                    new Token(Token.NAME_TYPE, 'baz', 22),
                    new Token(Token.PUNCTUATION_TYPE, '[', 25),
                    new Token(Token.NUMBER_TYPE, 4, 26),
                    new Token(Token.PUNCTUATION_TYPE, ']', 27)
                ]
            ],
            ['..', [new Token(Token.OPERATOR_TYPE, '..', 1)]],
            ['\'#foo\'', [new Token(Token.STRING_TYPE, '#foo', 1)]],
            ['"#foo"', [new Token(Token.STRING_TYPE, '#foo', 1)]]
        ];

        cases.forEach(testCase => {
            it(testCase[0], () => {
                const expression = testCase[0];
                const tokens = testCase[1].concat([
                    new Token(Token.EOF_TYPE, null, expression.length + 1)
                ]);
                expect(lexer.tokenize(expression)).toEqual(new TokenStream(tokens, expression));
            });
        });
    });
});
