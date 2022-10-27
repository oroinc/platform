import BinaryNode from './node/binary-node';
import Token from 'oroexpressionlanguage/js/library/token';
import OriginalParser from 'oroexpressionlanguage/js/library/parser';

class Parser extends OriginalParser {
    /**
     * @inheritdoc
     */
    parseExpression(precedence = 0) {
        let expr = this.getPrimary();
        let token = this.stream.current;

        while (
            token.test(Token.OPERATOR_TYPE) &&
            token.value in Parser.BINARY_OPERATORS &&
            Parser.BINARY_OPERATORS[token.value].precedence >= precedence
        ) {
            const operator = Parser.BINARY_OPERATORS[token.value];
            this.stream.next();
            const precedence1 = Parser.OPERATOR_LEFT === operator.associativity
                ? operator.precedence + 1 : operator.precedence;
            const expr1 = this.parseExpression(precedence1);
            expr = new BinaryNode(token.value, expr, expr1);

            token = this.stream.current;
        }

        if (0 === precedence) {
            return this.parseConditionalExpression(expr);
        }

        return expr;
    }
}

export default Parser;
