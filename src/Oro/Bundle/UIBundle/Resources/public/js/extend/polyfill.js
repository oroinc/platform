define(function() {
    'use strict';

    Math.log10 = Math.log10 || function(x) {
        return Math.log(x) / Math.LN10;
    };

    Math.sign = Math.sign || function(x) {
        x = +x; // convert to a number
        if (x === 0 || isNaN(x)) {
            return x;
        }
        return x > 0 ? 1 : -1;
    };
});

