define(function(require) {
    'use strict';

    var _ = require('underscore');
    var jsPlumb = require('jsplumb');
    var JsPlumb = jsPlumb.constructor;
    var _each;
    var _gel;

    /* original jsPlumb methods */
    /* jshint ignore:start */
    // jscs:disable
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
    // jscs:enable
    /* jshint ignore:end */
    /* original jsPlumb methods:end */

    /**
     * Override for initDraggable method to make the listener handler debounced
     */
    JsPlumb.prototype.initDraggable = _.wrap(JsPlumb.prototype.initDraggable, function(initDraggable, el) {
        initDraggable.apply(this, _.rest(arguments));
        _each(el, function(el) {
            el = _gel(el);
            if (el) {
                el._katavorioDrag.moveListener = _.debounce(el._katavorioDrag.moveListener, 30);
            }
        });
    });

    return jsPlumb;
});
