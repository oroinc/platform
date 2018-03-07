define(function(require) {
    'use strict';

    var SwipeActionsManager;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var error = require('oroui/js/error');

    var DEFAULT_OPTIONS = {
        minDistanceXAxis: 30,
        maxDistanceYAxis: 30,
        maxAllowedTime: 1000
    };

    /**
     *
     * @param {String} elementSelector
     * @param {Object} options
     * @returns {*}
     * @constructor
     */
    SwipeActionsManager = function(elementSelector, options) {
        if (!elementSelector) {
            return error.showErrorInConsole('"elementSelector" should be defined');
        }

        this.direction = null;
        this.touchStartCoords = null;
        this.touchEndCoords = null;
        this.elapsedTime = 0;
        this.startTime = 0;

        this.$el = $(elementSelector);

        this.swipeInitialize(options);
    };

    SwipeActionsManager.prototype = {
        swipeInitialize: function(options) {
            _.extend(this, _.defaults(_.pick(options,
                ['minDistanceXAxis', 'maxDistanceYAxis', 'maxAllowedTime']
            ), DEFAULT_OPTIONS));
            this._bindEvents();
        },

        _bindEvents: function() {
            this.$el.on('touchstart', _.bind(this._swipeStart, this));
            this.$el.on('touchmove', _.bind(this._swipeMove, this));
            this.$el.on('touchend', _.bind(this._swipeEnd, this));
        },

        _swipeStart: function(event) {
            event = ('changedTouches' in event) ? event.changedTouches[0] : event;

            this.touchStartCoords = {
                x: event.pageX,
                y: event.pageY
            };

            this.startTime = new Date().getTime();
        },

        _swipeMove: function(event) {
            event.preventDefault();
        },

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
                    this.direction = (this.touchEndCoords.x < 0) ? 'left' : 'right';
                    mediator.trigger('swipe-action-' + this.direction);
                }
            }
        }
    };

    return SwipeActionsManager;
});
