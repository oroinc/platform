define([
    'jquery'
], function($) {
    'use strict';

    var readyStateTracker = {
        deferreds: {},
        getDeferred: function(name) {
            if (!this.deferreds[name]) {
                this.deferreds[name] = $.Deferred();
            }
            return this.deferreds[name];
        },
        markReady: function(name) {
            this.getDeferred(name).resolve();
        },
        whenReady: function(name, cb, ctx) {
            this.getDeferred(name).done($.proxy(cb, ctx || window));
        }
    };

    if ($) {
        $(function() {
            readyStateTracker.markReady('dom');
        });
    }

    return readyStateTracker;
});
