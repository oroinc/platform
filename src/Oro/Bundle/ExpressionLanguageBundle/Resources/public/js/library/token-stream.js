define(function(require) {
    'use strict';

    var ExpressionSyntaxError = require('oroexpressionlanguage/js/library/expression-syntax-error');
    var Token = require('oroexpressionlanguage/js/library/token');

    /**
     * @param {Array.<Token>} tokens     An array of tokens
     * @param {string} expression
     */
    function TokenStream(tokens, expression) {
        this.tokens = tokens;
        this.current = tokens[0];
        this.expression = expression || '';
        this.position = 0;
    }

    TokenStream.prototype = {
        constructor: TokenStream,

        /**
         * Returns a string representation of the token stream.
         *
         * @return {string}
         */
        toString: function() {
            return this.tokens.join('\n');
        },

        /**
         * Sets the pointer to the next token and returns the old one.
         */
        next: function() {
            if (this.position + 1 >= this.tokens.length) {
                throw new ExpressionSyntaxError('Unexpected end of expression', this.current.cursor, this.expression);
            }

            ++this.position;

            this.current = this.tokens[this.position];
        },

        /**
         * Tests a token.
         *
         * @param {string} type  The type to test
         * @param {string} [value]  The token value
         * @param {string} [message]  The syntax error message
         */
        expect: function(type, value, message) {
            var token = this.current;
            if (!token.test(type, value)) {
                message = message ? message + '. ' : '';
                message += 'Unexpected token "' + token.type + '" of value "' + token.value + '"';
                message += ' ("' + type + '" expected';
                if (value) {
                    message += ' with value "' + value + '"';
                }
                message += ')';
                throw new ExpressionSyntaxError(message, token.cursor, this.expression);
            }
            this.next();
        },

        /**
         * Checks if end of stream was reached.
         *
         * @return {boolean}
         */
        isEOF: function() {
            return Token.EOF_TYPE === this.current.type;
        },

        /**
         * @return {string}
         */
        getExpression: function() {
            return this.expression;
        }
    };

    return TokenStream;
});
