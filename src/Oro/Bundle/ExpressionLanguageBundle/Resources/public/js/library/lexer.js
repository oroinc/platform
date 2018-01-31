define(function(require) {
    'use strict';

    var Token = require('oroexpressionlanguage/js/library/token');
    var TokenStream = require('oroexpressionlanguage/js/library/token-stream');
    var ExpressionSyntaxError = require('oroexpressionlanguage/js/library/expression-syntax-error');
    var stripcslashes = require('oroexpressionlanguage/lib/php-to-js/stripcslashes');

    function Lexer() {}

    Object.defineProperties(Lexer.prototype, {
        NUMBER_REGEXP: {value: /^[0-9]+(?:.[0-9]+)?/},
        STRING_REGEXP: {value: /^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/},
        NAME_REGEXP: {value: /^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/},
        OPERATION_REGEXP: {value: /^(not in(?=[\s(])|!==|not(?=[\s(])|and(?=[\s(])|===|>=|or(?=[\s(])|<=|\*\*|\.\.|in(?=[\s(])|&&|\|\||matches|==|!=|\*|~|%|\/|>|\||!|\^|&|\+|<|-)/}
    });

    Object.assign(Lexer.prototype, {
        constructor: Lexer,

        /**
         * Tokenizes an expression.
         *
         * @param {string} expression The expression to tokenize
         * @return {TokenStream} a token stream instance
         * @throws {ExpressionSyntaxError}
         */
        tokenize: function(expression) {
            expression = expression.replace(/[\r\n\t\v\f]/g, ' ');
            var cursor = 0;
            var tokens = [];
            var brackets = [];
            var end = expression.length;
            var match;
            var bracket;
            var message;
            var expressionPart;

            while (cursor < end) {
                if (' ' === expression[cursor]) {
                    ++cursor;

                    continue;
                }

                expressionPart = expression.substr(cursor);

                if (this.NUMBER_REGEXP.test(expressionPart)) {
                    // numbers
                    match = expressionPart.match(this.NUMBER_REGEXP);
                    var number = parseFloat(match[0]); // floats
                    if (match[0].match(/^[0-9]+/) && number <= Number.MAX_SAFE_INTEGER) {
                        number = parseInt(match[0], 10); // integers lower than the maximum
                    }
                    tokens.push(new Token(Token.NUMBER_TYPE, number, cursor + 1));
                    cursor += match[0].length;
                } else if ('([{'.indexOf(expression[cursor]) !== -1) {
                    // opening bracket
                    brackets.push([expression[cursor], cursor]);

                    tokens.push(new Token(Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                    ++cursor;
                } else if (')]}'.indexOf(expression[cursor]) !== -1) {
                    // closing bracket
                    if (!brackets.length) {
                        message = 'Unexpected "' + expression[cursor] + '"';
                        throw new ExpressionSyntaxError(message, cursor, expression);
                    }

                    bracket = brackets.pop();
                    if (expression[cursor] !== ({'(': ')', '[': ']', '{': '}'})[bracket[0]]) {
                        message = 'Unclosed "' + bracket[0] + '"';
                        throw new ExpressionSyntaxError(message, bracket[1], expression);
                    }

                    tokens.push(new Token(Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                    ++cursor;
                } else if (this.STRING_REGEXP.test(expressionPart)) {
                    // strings
                    match = expressionPart.match(this.STRING_REGEXP);
                    var string = match[0].substring(1, match[0].length - 1); // strip quotes
                    tokens.push(new Token(Token.STRING_TYPE, stripcslashes(string), cursor + 1));
                    cursor += match[0].length;
                } else if (this.OPERATION_REGEXP.test(expressionPart)) {
                    // operators
                    match = expressionPart.match(this.OPERATION_REGEXP);
                    tokens.push(new Token(Token.OPERATOR_TYPE, match[0], cursor + 1));
                    cursor += match[0].length;
                } else if ('.,?:'.indexOf(expression[cursor]) !== -1) {
                    // punctuation
                    tokens.push(new Token(Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                    ++cursor;
                } else if (this.NAME_REGEXP.test(expressionPart)) {
                    // names
                    match = expressionPart.match(this.NAME_REGEXP);
                    tokens.push(new Token(Token.NAME_TYPE, match[0], cursor + 1));
                    cursor += match[0].length;
                } else {
                    // unlexable
                    message = 'Unexpected character "' + expression[cursor] + '"';
                    throw new ExpressionSyntaxError(message, cursor, expression);
                }
            }

            tokens.push(new Token(Token.EOF_TYPE, null, cursor + 1));

            if (brackets.length) {
                bracket = brackets.pop();
                message = 'Unclosed "' + bracket[0] + '"';
                throw new ExpressionSyntaxError(message, bracket[1], expression);
            }

            return new TokenStream(tokens, expression);
        }
    });

    return Lexer;
});
