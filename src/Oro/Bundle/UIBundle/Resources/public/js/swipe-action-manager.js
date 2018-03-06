define(function(require) {
    'use strict';

    var SwipeActionsManager;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    SwipeActionsManager = function() {
        this.touchStartCoords = {x: -1, y: -1};
        this.touchEndCoords = {x: -1, y: -1};
        this.direction = undefined;
        this.minDistanceXAxis = 30;
        this.maxDistanceYAxis = 30;
        this.maxAllowedTime = 1000;
        this.startTime = 0;
        this.elapsedTime = 0;
        this.$el = $('body');

        this.swipeInitialize();
    };

    SwipeActionsManager.prototype = {
        swipeInitialize: function() {
            this._bindEvents();
        },

        _bindEvents: function() {
            this.$el.on('mousedown touchstart', _.bind(this._swipeStart, this));
            this.$el.on('mousemove touchmove', _.bind(this._swipeMove, this));
            this.$el.on('mouseup touchend', _.bind(this._swipeEnd, this));
        },

        _swipeStart: function(event) {
            event = event ? event : window.event;
            event = ('changedTouches' in event) ? event.changedTouches[0] : event;

            this.touchStartCoords = {
                x: event.pageX,
                y: event.pageY
            };

            this.startTime = new Date().getTime();
        },

        _swipeMove: function(event) {
            event = event ? event : window.event;
            event.preventDefault();
        },

        _swipeEnd: function(event) {
            event = event ? event : window.event;
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
                    this.direction = (this.touchEndCoords.x < 0) ? 'left' : 'right';
                    mediator.trigger('swipe-action-' + this.direction);
                }
            }
        }
    };

    return SwipeActionsManager;
});
