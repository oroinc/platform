define(function() {
    'use strict';

    function ComplexityError(message, fileName, lineNumber) {
        const instance = new Error(message, fileName, lineNumber);
        Object.setPrototypeOf(instance, Object.getPrototypeOf(this));
        if (Error.captureStackTrace) {
            Error.captureStackTrace(instance, ComplexityError);
        }
        return instance;
    }

    ComplexityError.prototype = Object.create(Error.prototype, {
        constructor: {
            value: Error,
            enumerable: false,
            writable: true,
            configurable: true
        }
    });

    return ComplexityError;
});
