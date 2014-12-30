/*jslint nomen:true*/
/*global define*/
define([
    'jquery'
], function (jquery) {
    'use strict';

    return {
        deferreds: {},
        getDeferred: function (name) {
            if (!this.deferreds[name]) {
                this.deferreds[name] = $.Deferred();
            }
            return this.deferreds[name];
        },
        markReady: function (name) {
            this.getDeferred(name).resolve();
        },
        whenReady: function (name, cb, ctx) {
            this.getDeferred(name).done(jQuery.proxy(cb, ctx || window));
        }
    };
});
