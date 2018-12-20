define(function(require) {
    'use strict';

    var SwipeableView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    SwipeableView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['minDistanceXAxis', 'maxDistanceYAxis', 'maxAllowedTime']),

        /** @type {string} */
        direction: void 0,

        /** @type {{x: Number, y: Number}|null} */
        touchStartCoords: null,

        /** @type {{x: Number, y: Number}|null} */
        touchEndCoords: null,

        /** @type {Number} */
        elapsedTime: 0,

        /** @type {Number} */
        startTime: 0,

        minDistanceXAxis: 30,

        maxDistanceYAxis: 30,

        maxAllowedTime: 1000,

        events: {
            touchstart: '_swipeStart',
            touchmove: '_swipeMove',
            touchend: '_swipeEnd'
        },

        /**
         * Swipe actions on mobile devices
         *
         * @inheritDoc
         */
        constructor: function SwipeableView(options) {
            SwipeableView.__super__.constructor.call(this, options);
        },

        /**
         * Handler for start touch
         *
         * @param {jQuery.Event} event
         * @private
         */
        _swipeStart: function(event) {
            event = ('changedTouches' in event) ? event.changedTouches[0] : event;

            this.touchStartCoords = {
                x: event.pageX,
                y: event.pageY
            };

            this.startTime = new Date().getTime();

            mediator.trigger('swipe-action-start', {}, event.target);
        },

        /**
         * Handler for start move
         *
         * @param {jQuery.Event} event
         * @private
         */
        _swipeMove: function(event) {
            event = ('changedTouches' in event) ? event.changedTouches[0] : event;

            var touchEndCoords = {
                x: event.pageX - this.touchStartCoords.x,
                y: event.pageY - this.touchStartCoords.y
            };

            mediator.trigger('swipe-action-move', this._collectOptions(touchEndCoords), event.target);
        },

        /**
         * Handler for end touch and fire external mediator event
         *
         * @param {jQuery.Event} event
         * @private
         */
        _swipeEnd: function(event) {
            event = ('changedTouches' in event) ? event.changedTouches[0] : event;

            this.touchEndCoords = {
                x: event.pageX - this.touchStartCoords.x,
                y: event.pageY - this.touchStartCoords.y
            };
            this.elapsedTime = new Date().getTime() - this.startTime;

            if (this.elapsedTime <= this.maxAllowedTime) {
                if (
                    Math.abs(this.touchEndCoords.x) >= this.minDistanceXAxis &&
                    Math.abs(this.touchEndCoords.y) <= this.maxDistanceYAxis
                ) {
                    this.direction = this._getDirection(this.touchEndCoords.x);
                    mediator.trigger('swipe-action-' + this.direction, this.touchEndCoords, event.target);
                }
            }

            mediator.trigger('swipe-action-end', this._collectOptions(this.touchEndCoords), event.target);
        },

        _getDirection: function(coords) {
            return (coords < 0) ? 'left' : 'right';
        },

        _collectOptions: function(options) {
            return _.extend({}, options, {
                direction: this._getDirection(options.x)
            });
        }
    });

    return SwipeableView;
});
