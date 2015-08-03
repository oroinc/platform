define({
    xor: function(a, b) {
        'use strict';
        return a ? !b : b;
    },
    between: function(b, a, c) {
        'use strict';
        return (a <= b && b <= c) || (a >= b && b >= c);
    },
    betweenNonInclusive: function(b, a, c) {
        'use strict';
        return (a < b && b < c) || (a > b && b > c);
    }
});
