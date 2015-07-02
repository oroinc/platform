define(function (require) {
    'use strict';
    var _ = require('underscore'),
        Backbone = require('backbone'),
        Rule = function (context) {
            this.context = context || this;
            this.root = null;
            this.items = [];
        }
    _.extend(Rule.prototype, {

        priority: 10,

        match: function(){
            return false;
        },

        apply: function(){
            return;
        }
    });

    Rule.extend = Backbone.Model.extend;

    return Rule;
});
