import ExpressionFunction from 'oroexpressionlanguage/js/library/expression-function';

export default {
    getFunctions() {
        return [
            new ExpressionFunction('containsRegExp', function() {}, function() {}),
            new ExpressionFunction('startWithRegExp', function() {}, function() {}),
            new ExpressionFunction('endWithRegExp', function() {}, function() {}),
            new ExpressionFunction('wayOfWeek', function() {}, function() {}),
            new ExpressionFunction('week', function() {}, function() {}),
            new ExpressionFunction('dayOfMonth', function() {}, function() {}),
            new ExpressionFunction('month', function() {}, function() {}),
            new ExpressionFunction('quarter', function() {}, function() {}),
            new ExpressionFunction('dayOfYear', function() {}, function() {}),
            new ExpressionFunction('year', function() {}, function() {}),
            new ExpressionFunction('currentDayOfWeek', function() {}, function() {}),
            new ExpressionFunction('currentWeek', function() {}, function() {}),
            new ExpressionFunction('currentDayOfMonth', function() {}, function() {}),
            new ExpressionFunction('currentMonth', function() {}, function() {}),
            new ExpressionFunction('firstMonthOfCurrentQuarter', function() {}, function() {}),
            new ExpressionFunction('currentQuarter', function() {}, function() {}),
            new ExpressionFunction('currentDayOfYear', function() {}, function() {}),
            new ExpressionFunction('firstDayOfCurrentQuarter', function() {}, function() {}),
            new ExpressionFunction('currentYear', function() {}, function() {}),
            new ExpressionFunction('now', function() {}, function() {}),
            new ExpressionFunction('today', function() {}, function() {}),
            new ExpressionFunction('startOfTheWeek', function() {}, function() {}),
            new ExpressionFunction('startOfTheMonth', function() {}, function() {}),
            new ExpressionFunction('startOfTheQuarter', function() {}, function() {}),
            new ExpressionFunction('startOfTheYear', function() {}, function() {}),
            new ExpressionFunction('thisDayWithoutYear', function() {}, function() {}),
            new ExpressionFunction('currentMonthWithoutYear', function() {}, function() {})
        ];
    }
};
