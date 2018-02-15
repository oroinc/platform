define(function(require) {
    'use strict';

    var OriginalToken = require('oroexpressionlanguage/js/library/token');

    /**
     * @param {string} type - The type of the token (Token.*_TYPE)
     * @param {string|number|null} value - The token value
     * @param {number} cursor - The cursor position in the source
     * @param {number} [length] - length of string that represents the token into expression
     */
    function Token(type, value, cursor, length) {
        Token.__super__.constructor.call(this, type, value, cursor);
        this.length = length || 1;
    }

    Object.defineProperties(Token, {
        EOF_TYPE: {value: OriginalToken.EOF_TYPE},
        NAME_TYPE: {value: OriginalToken.NAME_TYPE},
        NUMBER_TYPE: {value: OriginalToken.NUMBER_TYPE},
        STRING_TYPE: {value: OriginalToken.STRING_TYPE},
        OPERATOR_TYPE: {value: OriginalToken.OPERATOR_TYPE},
        PUNCTUATION_TYPE: {value: OriginalToken.PUNCTUATION_TYPE},
        ERROR_TYPE: {value: 'error'}
    });

    Token.prototype = Object.create(OriginalToken.prototype);
    Token.__super__ = OriginalToken.prototype;

    Object.assign(Token.prototype, {
        constructor: Token,
        /**
         * Length of string that represents the token into expression
         * @type {number}
         */
        length: void 0
    });

    return Token;
});
