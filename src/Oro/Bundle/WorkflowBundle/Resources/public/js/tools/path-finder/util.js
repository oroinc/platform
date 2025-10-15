export default {
    between(b, a, c) {
        return (a <= b && b <= c) || (a >= b && b >= c);
    },
    betweenNonInclusive(b, a, c) {
        return (a < b && b < c) || (a > b && b > c);
    }
};
