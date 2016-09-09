define(['underscore'], function(_) {
    'use strict';

    /**
     * Generates event list declaration which selects behavior based on testFnName
     *
     * @param {Function} View - constructor
     * @param {String} testFnName
     * @param {Object} extendedEvents
     * @constructor
     */
    function SplitEventList(View, testFnName, extendedEvents) {
        this.View = View;
        this.extendedEvents = extendedEvents;
        this.testFnName = testFnName;
        this.canGenerateEventsAsObject = !_.isFunction(View.prototype.events);
    }

    SplitEventList.prototype = {
        canGenerateEventsAsObject: false,
        /**
         * Select one of two handlers and run it.
         * NOTE: this function will run with view as context
         *
         * @param {String} testFnName
         * @param {Function} trueFn
         * @param {Function} falseFn
         * @param {jQuery.Event} e
         */
        selectAndRun: function(testFnName, trueFn, falseFn, e) {
            if (this[testFnName]()) {
                if (_.isString(trueFn)) {
                    trueFn = this[trueFn];
                }
                if (!trueFn) {
                    return;
                }
                trueFn.call(this, e);
            } else {
                if (_.isString(falseFn)) {
                    falseFn = this[falseFn];
                }
                if (!falseFn) {
                    return;
                }
                falseFn.call(this, e);
            }
        },

        /**
         * @param {Backbone.View} [view]
         * @returns {Object}
         */
        generateEvents: function(view) {
            var oldEvents = this.canGenerateEventsAsObject ?
                this.View.prototype.events :
                this.View.prototype.events.call(view);
            var events = Object.create(oldEvents);
            for (var key in this.extendedEvents) {
                if (this.extendedEvents.hasOwnProperty(key)) {
                    events[key] = _.partial(
                        this.selectAndRun,
                        this.testFnName,
                        this.extendedEvents[key],
                        oldEvents[key]
                    );
                }
            }
            return events;
        },

        generateDeclaration: function() {
            var _this = this;
            return this.canGenerateEventsAsObject ?
                this.generateEvents() :
                function() {
                    _this.generateEvents(this);
                };
        }
    };

    return SplitEventList;
});
