define(function (require) {
    'use strict';
    var _ = require('underscore'),
        Rule = function (context) {
            this.context = context || this;
            this.root = null;
            this.items = [];
        }
    _.extend(Rule.prototype, {

        prio: 10,

        match: function(){
            return false;
        },

        apply: function(){
            return;
        }
    });

    Rule.extend = function(protoProps, staticProps) {
        var parent = this;
        var child;

        if (protoProps && _.has(protoProps, 'constructor')) {
            child = protoProps.constructor;
        } else {
            child = function(){ return parent.apply(this, arguments); };
        }

        _.extend(child, parent, staticProps);

        var Surrogate = function(){ this.constructor = child; };
        Surrogate.prototype = parent.prototype;
        child.prototype = new Surrogate;

        if (protoProps) _.extend(child.prototype, protoProps);

        child.__super__ = parent.prototype;

        return child;
    };

    return Rule;
});
