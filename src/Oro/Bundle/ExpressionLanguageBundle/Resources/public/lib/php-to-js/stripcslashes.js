define(function() {
    'use strict';

    /**
     * equivalent to PHP's stripcslashes
     */
    return function stripcslashes(str) {
        return str.replace(/\\(.)/g, function(match, char) {
            return {
                t: '\t',
                r: '\r',
                n: '\n',
                a: '\a',
                f: '\f',
                v: '\v',
                0: '\0'
            }[char] || char;
        });
    };
});
