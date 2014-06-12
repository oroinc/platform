/*global define*/
define([
    'chaplin'
], function (Chaplin) {
    'use strict';

    var Controller, reuses;

    reuses = [];

    Controller = Chaplin.Controller.extend({
        /**
         * Handles before-action activity
         *
         * @override
         */
        beforeAction: function (params, route, options) {
            var i;
            Chaplin.Controller.prototype.beforeAction.apply(this, arguments);

            // compose global instances
            for (i = 0; i < reuses.length; i += 1) {
                this.reuse.apply(this, reuses[i]);
            }
        }
    });

    /**
     * Collects compositions to reuse before controller action
     * @static
     */
    Controller.addBeforeActionReuse = function () {
        var args = Array.prototype.slice.call(arguments, 0);
        reuses.push(args);
    };

    return Controller;
});
