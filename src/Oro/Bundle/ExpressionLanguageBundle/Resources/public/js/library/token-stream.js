import ExpressionSyntaxError from 'oroexpressionlanguage/js/library/expression-syntax-error';
import Token from 'oroexpressionlanguage/js/library/token';

class TokenStream {
    /**
     * @param {Array.<Token>} tokens An array of tokens
     * @param {string} [expression]
     */
    constructor(tokens, expression = '') {
        this.tokens = tokens;
        this.current = tokens[0];
        this.expression = expression;
        this.position = 0;
    }

    /**
     * Returns a string representation of the token stream.
     *
     * @return {string}
     */
    toString() {
        return this.tokens.join('\n');
    }

    /**
     * Sets the pointer to the next token and returns the old one.
     */
    next() {
        if (this.position + 1 >= this.tokens.length) {
            throw new ExpressionSyntaxError('Unexpected end of expression', this.current.cursor, this.expression);
        }

        ++this.position;

        this.current = this.tokens[this.position];
    }

    /**
     * Tests a token.
     *
     * @param {string} type  The type to test
     * @param {string} [value]  The token value
     * @param {string} [message]  The syntax error message
     */
    expect(type, value, message) {
        const token = this.current;
        if (!token.test(type, value)) {
            message = message ? message + '. ' : '';
            message += `Unexpected token "${token.type}" of value "${token.value}"`;
            message += ` ("${type}" expected`;
            if (value) {
                message += ` with value "${value}"`;
            }
            message += ')';
            throw new ExpressionSyntaxError(message, token.cursor, this.expression);
        }
        this.next();
    }

    /**
     * Checks if end of stream was reached.
     *
     * @return {boolean}
     */
    isEOF() {
        return Token.EOF_TYPE === this.current.type;
    }

    /**
     * @return {string}
     */
    getExpression() {
        return this.expression;
    }
}

export default TokenStream;
