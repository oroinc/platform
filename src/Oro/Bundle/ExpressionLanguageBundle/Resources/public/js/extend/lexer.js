import OriginalLexer from 'oroexpressionlanguage/js/library/lexer';
import Token from './token';
import TokenStream from 'oroexpressionlanguage/js/library/token-stream';
import ExpressionSyntaxError from 'oroexpressionlanguage/js/library/expression-syntax-error';
import stripcslashes from 'oroexpressionlanguage/lib/php-to-js/stripcslashes';

class Lexer extends OriginalLexer {
    // added '='
    // removed '===', '!=='
    static OPERATION_REGEXP = /^(not in(?=[\s(])|not(?=[\s(])|and(?=[\s(])|>=|or(?=[\s(])|<=|\*\*|\.\.|in(?=[\s(])|&&|\|\||matches|==|=|!=|\*|~|%|\/|>|\||!|\^|&|\+|<|-)/;

    /**
     * Tokenizes an expression.
     *
     * @param {string} expression The expression to tokenize
     * @param {function(string, number, string)} [errorHandler] - callback that throws an error
     * @return {TokenStream} a token stream instance
     * @protected
     */
    _tokenize(expression, errorHandler) {
        expression = expression.replace(/[\r\n\t\v\f]/g, ' ');
        let cursor = 0;
        const tokens = [];
        const brackets = [];
        const end = expression.length;
        let match;
        let bracket;
        let message;
        let expressionPart;

        while (cursor < end) {
            if (' ' === expression[cursor]) {
                ++cursor;

                continue;
            }

            expressionPart = expression.substr(cursor);

            if (Lexer.NUMBER_REGEXP.test(expressionPart)) {
                // numbers
                match = expressionPart.match(Lexer.NUMBER_REGEXP);
                let number = parseFloat(match[0]); // floats
                if (match[0].match(/^[0-9]+/) && number <= Number.MAX_SAFE_INTEGER) {
                    number = parseInt(match[0], 10); // integers lower than the maximum
                }
                tokens.push(new Token(Token.NUMBER_TYPE, number, cursor + 1, match[0].length));
                cursor += match[0].length;
            } else if ('([{'.indexOf(expression[cursor]) !== -1) {
                // opening bracket
                brackets.push([expression[cursor], cursor]);

                tokens.push(new Token(Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                ++cursor;
            } else if (')]}'.indexOf(expression[cursor]) !== -1) {
                // closing bracket
                if (!brackets.length) {
                    message = `Unexpected "${expression[cursor]}"`;
                    errorHandler(message, cursor, expression);
                    tokens.push(new Token(Token.ERROR_TYPE, message, cursor + 1));
                    ++cursor;
                    continue;
                }

                bracket = brackets.pop();
                if (expression[cursor] !== ({'(': ')', '[': ']', '{': '}'})[bracket[0]]) {
                    message = `Unclosed "${bracket[0]}"`;
                    errorHandler(message, bracket[1], expression);
                    tokens.push(new Token(Token.ERROR_TYPE, message, cursor + 1));
                    ++cursor;
                    continue;
                }

                tokens.push(new Token(Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                ++cursor;
            } else if (Lexer.STRING_REGEXP.test(expressionPart)) {
                // strings
                match = expressionPart.match(Lexer.STRING_REGEXP);
                const string = match[0].substring(1, match[0].length - 1); // strip quotes
                tokens.push(new Token(Token.STRING_TYPE, stripcslashes(string), cursor + 1, match[0].length));
                cursor += match[0].length;
            } else if (Lexer.OPERATION_REGEXP.test(expressionPart)) {
                // operators
                match = expressionPart.match(Lexer.OPERATION_REGEXP);
                tokens.push(new Token(Token.OPERATOR_TYPE, match[0], cursor + 1, match[0].length));
                cursor += match[0].length;
            } else if ('.,?:'.indexOf(expression[cursor]) !== -1) {
                // punctuation
                tokens.push(new Token(Token.PUNCTUATION_TYPE, expression[cursor], cursor + 1));
                ++cursor;
            } else if (Lexer.NAME_REGEXP.test(expressionPart)) {
                // names
                match = expressionPart.match(Lexer.NAME_REGEXP);
                tokens.push(new Token(Token.NAME_TYPE, match[0], cursor + 1, match[0].length));
                cursor += match[0].length;
            } else {
                // unlexable
                message = `Unexpected character "${expression[cursor]}"`;
                errorHandler(message, cursor, expression);
                tokens.push(new Token(Token.ERROR_TYPE, message, cursor + 1));
                ++cursor;
            }
        }

        tokens.push(new Token(Token.EOF_TYPE, null, cursor + 1));

        if (brackets.length && errorHandler.length !== 0) {
            // if the method is called with stub error handler just skip it
            bracket = brackets.pop();
            message = `Unclosed "${bracket[0]}"`;
            errorHandler(message, bracket[1], expression);
        }

        return new TokenStream(tokens, expression);
    }

    /**
     * @inheritdoc
     */
    tokenize(expression) {
        const errorHandler = (message, cursor, expression) => {
            throw new ExpressionSyntaxError(message, cursor, expression);
        };
        return this._tokenize(expression, errorHandler);
    }

    /**
     * Tokenizes an expression even containing errors.
     *
     * @param {string} expression The expression to tokenize
     * @return {TokenStream} a token stream instance
     */
    tokenizeForce(expression) {
        return this._tokenize(expression, () => {});
    }
}

export default Lexer;
