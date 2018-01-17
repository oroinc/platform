define(function() {
    'use strict';

    function ExpressionSyntaxError(message, cursor, expression) {
        message += ' around position ' + (cursor || 0);
        if (expression) {
            message += ' for expression `' + expression + '`';
        }
        message += '.';

        var error = new Error(message);
        Object.setPrototypeOf(error, Object.getPrototypeOf(this));
        return error;
    }

    ExpressionSyntaxError.prototype = Object.create(Error.prototype, {
        constructor: {
            value: ExpressionSyntaxError,
            writable: true,
            configurable: true
        },
        message: {value: ''},
        name: {value: 'ExpressionSyntaxError'}
    });

    return ExpressionSyntaxError;
});
