class Token {
    static EOF_TYPE = 'end of expression';
    static NAME_TYPE = 'name';
    static NUMBER_TYPE = 'number';
    static STRING_TYPE = 'string';
    static OPERATOR_TYPE = 'operator';
    static PUNCTUATION_TYPE = 'punctuation';

    /**
     * @param {string} type   The type of the token (Token.*_TYPE)
     * @param {string|number|null} value  The token value
     * @param {number} cursor The cursor position in the source
     */
    constructor(type, value, cursor) {
        this.type = type;
        this.value = value;
        this.cursor = cursor;
    }

    /**
     * Returns a string representation of the token.
     *
     * @return {string}
     */
    toString() {
        const value = this.value !== null && this.value !== void 0 ? this.value : '';
        return String(this.cursor).padStart(3) + ' ' + this.type.toUpperCase().padEnd(11) + ' ' + value;
    }

    /**
     * Tests the current token for a type and/or a value.
     *
     * @param {string} type  The type to test
     * @param {string} [value] The token value
     * @return {boolean}
     */
    test(type, value) {
        return this.type === type && (value === void 0 || this.value === value);
    }
}

export default Token;
