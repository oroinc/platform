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

    // copied from https://developer.mozilla.org/
    /* jshint ignore:start */
    // jscs:disable
    if (!Function.prototype.bind) {
        Function.prototype.bind = function(oThis) {
            if (typeof this !== 'function') {
                // closest thing possible to the ECMAScript 5
                // internal IsCallable function
                throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
            }

            var aArgs   = Array.prototype.slice.call(arguments, 1),
                fToBind = this,
                fNOP    = function() {},
                fBound  = function() {
                    return fToBind.apply(this instanceof fNOP
                            ? this
                            : oThis,
                        aArgs.concat(Array.prototype.slice.call(arguments)));
                };

            if (this.prototype) {
                // Function.prototype doesn't have a prototype property
                fNOP.prototype = this.prototype;
            }
            fBound.prototype = new fNOP();

            return fBound;
        };
    }
    // jscs:enable
    /* jshint ignore:end */
});
