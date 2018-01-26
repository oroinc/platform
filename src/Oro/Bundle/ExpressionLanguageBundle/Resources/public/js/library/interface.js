define(function() {
    'use strict';

    function Interface(methods) {
        this.methods = methods || {};
    }

    Interface.prototype = {
        constructor: Interface,

        expectToBeImplementedBy: function(obj) {
            var missingMethods = [];
            for (var name in this.methods) {
                if (
                    this.methods.hasOwnProperty(name) &&
                    (typeof obj[name] !== 'function' || obj[name].length !== this.methods[name].length)
                ) {
                    missingMethods.push(name);
                }
            }
            if (missingMethods.length !== 0) {
                missingMethods = missingMethods.map(function(name) {
                    return '`' + name + '`';
                });
                var message;
                if (missingMethods.length > 1) {
                    message = 'Methods ' + missingMethods.join(', ') + ' are required.';
                } else {
                    message = 'Method ' + missingMethods[0] + ' is required.';
                }
                throw new TypeError(message);
            }
        }
    };

    return Interface;
});
