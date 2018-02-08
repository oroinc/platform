define(function(require) {
    'use strict';

    var BinaryNode = require('oroexpressionlanguage/js/extend/node/binary-node');
    var OriginalParser = require('oroexpressionlanguage/js/library/parser');
    var Token = require('oroexpressionlanguage/js/library/token');

    function Parser() {
        Parser.__super__.constructor.call(this);
    }

    Parser.prototype = Object.create(OriginalParser.prototype);
    Parser.__super__ = OriginalParser.prototype;

    Object.assign(Parser.prototype, {
        constructor: Parser,

        parseExpression: function(precedence) {
            precedence = precedence || 0;
            var expr = this.getPrimary();
            var token = this.stream.current;

            while (
                token.test(Token.OPERATOR_TYPE) &&
                token.value in this.binaryOperators &&
                this.binaryOperators[token.value].precedence >= precedence
            ) {
                var operator = this.binaryOperators[token.value];
                this.stream.next();
                var precedence1 = this.OPERATOR_LEFT === operator.associativity ?
                    operator.precedence + 1 : operator.precedence;
                var expr1 = this.parseExpression(precedence1);
                expr = new BinaryNode(token.value, expr, expr1);

                token = this.stream.current;
            }

            if (0 === precedence) {
                return this.parseConditionalExpression(expr);
            }

            return expr;
        }
    });

    return Parser;
});
