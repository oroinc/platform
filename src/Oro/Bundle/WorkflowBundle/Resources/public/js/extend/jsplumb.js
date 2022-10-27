define(function(require) {
    'use strict';

    const _ = require('underscore');
    const jsPlumb = require('jsplumb').jsPlumb;
    const JsPlumb = jsPlumb.constructor;
    let _each;
    let _gel;

    /* original jsPlumb methods */
    /* eslint-disable */
    var _uuid = function() {
        return ('xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        }));
    };

    _each = function(obj, fn) {
        if (obj == null) return;
        obj = (typeof obj !== "string") && (obj.tagName == null && obj.length != null) ? obj : [ obj ];
        for (var i = 0; i < obj.length; i++)
            fn.apply(obj[i], [ obj[i] ]);
    };

    _gel = function(el) {
        if (el == null) return null;
        el = typeof el === "string" ? document.getElementById(el) : el;
        if (el == null) return null;
        el._katavorio = el._katavorio || _uuid();
        return el;
    };
    /* eslint-enable */
    /* original jsPlumb methods:end */

    // Returns a function, that, when invoked, will only be triggered at most once
    // during a given window of time, also it execution wil be postponed till repaint cycle.
    // Normally, the throttled function will run
    // as much as it can, without ever going more than once per `wait` duration;
    // but if you'd like to disable the execution on the leading edge, pass
    // `{leading: false}`. To disable execution on the trailing edge, ditto.
    const throttleAndWaitRepaint = !_.isFunction(window.requestAnimationFrame)
        ? _.throttle
        : function(func, wait, options) {
            let context;
            let args;
            let result;
            let timeout = null;
            let previous = 0;
            let locked = false;
            if (!options) {
                options = {};
            }
            const later = function() {
                if (locked) {
                    timeout = setTimeout(later, 0);
                    return;
                }
                previous = options.leading === false ? 0 : _.now();
                timeout = null;
                result = func.apply(context, args);
                if (!timeout) {
                    context = args = null;
                }
            };
            const unlock = function() {
                locked = false;
            };
            return function(...params) {
                const now = _.now();
                if (!previous && options.leading === false) {
                    previous = now;
                }
                const remaining = wait - (now - previous);
                context = this;
                args = params;
                if (remaining <= 0 || remaining > wait) {
                    if (locked) {
                        return;
                    }
                    clearTimeout(timeout);
                    previous = now;
                    timeout = setTimeout(later, 0);
                    locked = true;
                    requestAnimationFrame(unlock);
                } else if (!timeout && options.trailing !== false) {
                    timeout = setTimeout(later, remaining);
                    locked = true;
                    requestAnimationFrame(unlock);
                }
                return result;
            };
        };

    /**
     * Override for initDraggable method to make the listener handler debounced
     */
    JsPlumb.prototype.initDraggable = _.wrap(JsPlumb.prototype.initDraggable, function(initDraggable, el, ...rest) {
        initDraggable.call(this, el, ...rest);
        _each(el, function(el) {
            el = _gel(el);
            if (el) {
                el._katavorioDrag.moveListener = throttleAndWaitRepaint(el._katavorioDrag.moveListener, 10);
            }
        });
    });

    return jsPlumb;
});
