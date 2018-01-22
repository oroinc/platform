define(function() {
    'use strict';

    /**
     * @param {string} type   The type of the token (Token.*_TYPE)
     * @param {string|number|null} value  The token value
     * @param {number} cursor The cursor position in the source
     */
    function Token(type, value, cursor) {
        this.type = type;
        this.value = value;
        this.cursor = cursor;
    }

    Object.defineProperties(Token, {
        EOF_TYPE: {value: 'end of expression'},
        NAME_TYPE: {value: 'name'},
        NUMBER_TYPE: {value: 'number'},
        STRING_TYPE: {value: 'string'},
        OPERATOR_TYPE: {value: 'operator'},
        PUNCTUATION_TYPE: {value: 'punctuation'}
    });

    Token.prototype = {
        constructor: Token,

        /**
         * Returns a string representation of the token.
         *
         * @return {string}
         */
        toString: function() {
            var value = this.value !== null && this.value !== void 0 ? this.value : '';
            return String(this.cursor).padStart(3) + ' ' + this.type.toUpperCase().padEnd(11) + ' ' + value;
        },

        /**
         * Tests the current token for a type and/or a value.
         *
         * @param {string} type  The type to test
         * @param {string} [value] The token value
         * @return {boolean}
         */
        test: function(type, value) {
            return this.type === type && (value === void 0 || this.value === value);
        }
    };

    return Token;
});
