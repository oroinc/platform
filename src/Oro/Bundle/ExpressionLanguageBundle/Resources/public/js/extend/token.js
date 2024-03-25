import OriginalToken from 'oroexpressionlanguage/js/library/token';

class Token extends OriginalToken {
    static ERROR_TYPE = 'error';

    /**
     * @param {string} type - The type of the token (Token.*_TYPE)
     * @param {string|number|null} value - The token value
     * @param {number} cursor - The cursor position in the source
     * @param {number} [length] - length of string that represents the token into expression
     */
    constructor(type, value, cursor, length = 1) {
        super(type, value, cursor);
        this.length = length;
    }
}

export default Token;
