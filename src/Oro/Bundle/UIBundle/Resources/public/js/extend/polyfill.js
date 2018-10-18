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
    /* eslint-disable */
    Object.setPrototypeOf = Object.setPrototypeOf || function(obj, proto) {
        obj.__proto__ = proto;
        return obj;
    };

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

    if (typeof Object.assign !== 'function') {
        // Must be writable: true, enumerable: false, configurable: true
        Object.defineProperty(Object, "assign", {
            value: function assign(target, varArgs) { // .length of function is 2
                'use strict';
                if (target == null) { // TypeError if undefined or null
                    throw new TypeError('Cannot convert undefined or null to object');
                }

                var to = Object(target);

                for (var index = 1; index < arguments.length; index++) {
                    var nextSource = arguments[index];

                    if (nextSource != null) { // Skip over if undefined or null
                        for (var nextKey in nextSource) {
                            // Avoid bugs when hasOwnProperty is shadowed
                            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                                to[nextKey] = nextSource[nextKey];
                            }
                        }
                    }
                }
                return to;
            },
            writable: true,
            configurable: true
        });
    }

    if (typeof Object.values !== 'function') {
        Object.values = function values(obj) {
            return Object.keys(obj).reduce(function(values, key) {
                return values.concat([obj[key]]);
            }, []);
        };
    }

    if (!String.prototype.padStart) {
        String.prototype.padStart = function padStart(targetLength,padString) {
            targetLength = targetLength>>0; //truncate if number or convert non-number to 0;
            padString = String((typeof padString !== 'undefined' ? padString : ' '));
            if (this.length > targetLength) {
                return String(this);
            }
            else {
                targetLength = targetLength-this.length;
                if (targetLength > padString.length) {
                    padString += padString.repeat(targetLength/padString.length); //append to original to ensure we are longer than needed
                }
                return padString.slice(0,targetLength) + String(this);
            }
        };
    }

    if (!String.prototype.padEnd) {
        String.prototype.padEnd = function padEnd(targetLength,padString) {
            targetLength = targetLength>>0; //floor if number or convert non-number to 0;
            padString = String((typeof padString !== 'undefined' ? padString : ' '));
            if (this.length > targetLength) {
                return String(this);
            }
            else {
                targetLength = targetLength-this.length;
                if (targetLength > padString.length) {
                    padString += padString.repeat(targetLength/padString.length); //append to original to ensure we are longer than needed
                }
                return String(this) + padString.slice(0,targetLength);
            }
        };
    }

    if (!String.prototype.repeat) {
        String.prototype.repeat = function(count) {
            'use strict';
            if (this == null) {
                throw new TypeError('can\'t convert ' + this + ' to object');
            }
            var str = '' + this;
            count = +count;
            if (count != count) {
                count = 0;
            }
            if (count < 0) {
                throw new RangeError('repeat count must be non-negative');
            }
            if (count == Infinity) {
                throw new RangeError('repeat count must be less than infinity');
            }
            count = Math.floor(count);
            if (str.length == 0 || count == 0) {
                return '';
            }
            // Ensuring count is a 31-bit integer allows us to heavily optimize the
            // main part. But anyway, most current (August 2014) browsers can't handle
            // strings 1 << 28 chars or longer, so:
            if (str.length * count >= 1 << 28) {
                throw new RangeError('repeat count must not overflow maximum string size');
            }
            var rpt = '';
            for (var i = 0; i < count; i++) {
                rpt += str;
            }
            return rpt;
        }
    }
    /* eslint-enable */
});
