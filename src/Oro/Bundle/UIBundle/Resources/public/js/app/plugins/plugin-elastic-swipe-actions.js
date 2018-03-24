define(function(require) {
    'use strict';

    var ElasticSwipeActions;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    ElasticSwipeActions = BasePlugin.extend({
        currentTarget: null,

        currentSwipedContainer: null,

        defaults: {
            limit: 50,
            maxLimit: 105,
            direction: 'left',
            swipeDoneClassName: 'swipe-done',
            elastic: false
        },

        containerSelector: null,

        limit: 50,

        maxLimit: 105,

        swipeDoneClassName: 'swipe-done',

        elastic: false,

        storedPos: 0,

        viewport: {
            minScreenType: 'any'
        },

        initialize: function(main, options) {
            _.extend(this, _.pick(options || {},
                ['containerSelector', 'limit', 'maxLimit', 'swipeDoneClassName', 'elastic', 'viewport']
            ));
            this._bindEvents();
        },

        destroy: function() {
            this._revertState();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.destroy();

            return ElasticSwipeActions.__super__.dispose.apply(this, arguments);
        },

        _bindEvents: function() {
            mediator.on('swipe-action-start', _.bind(this._onStart, this));
            mediator.on('swipe-action-move', _.bind(this._onMove, this));
            mediator.on('swipe-action-end', _.bind(this._onEnd, this));
        },

        _onStart: function(data, target) {
            this.currentTarget = $(target);
            var container = this.currentTarget.closest(this.containerSelector);

            if (
                this.currentSwipedContainer &&
                !$(this.currentSwipedContainer).is(container)
            ) {
                this._revertState();
            }

            this.currentSwipedContainer = container;
            this.currentSwipedContainer.css({
                transition: ''
            });

            if (this.currentSwipedContainer.hasClass(this.swipeDoneClassName)) {
                this.storedPos = parseInt(this.currentSwipedContainer.data('offset'));
            }

            this.currentSwipedContainer.addClass('swipe-active');
        },

        _onMove: function(data) {
            var xAxe = data.x - this.storedPos;

            if (
                !this.elastic &&
                (
                    (data.direction === 'left' && Math.abs(xAxe) > this.maxLimit) ||
                    (data.direction === 'right' && xAxe > 0)
                )
            ) {
                return;
            }

            // this.currentSwipedContainer.data('offset', data.x);
            this.currentSwipedContainer.css({
                transform: 'translateX(' + xAxe + 'px)'
            });
        },

        _onEnd: function(data) {
            var xAxe = data.x - this.storedPos;
            if (Math.abs(xAxe) < this.limit) {
                this._revertState();
                return;
            }

            if (data.direction === 'left' && Math.abs(xAxe) > this.limit) {
                this.currentSwipedContainer.data('offset', this.maxLimit);
                this.currentSwipedContainer.css({
                    transform: 'translateX(-' + this.maxLimit + 'px)',
                    transition: 'all 200ms ease-out'
                });

                this.storedPos = 0;

                this.currentSwipedContainer.addClass(this.swipeDoneClassName);
            }

            this.currentSwipedContainer.removeClass('swipe-active');
        },

        _revertState: function() {
            this.currentSwipedContainer.data('offset', 0);
            this.storedPos = 0;
            this.currentSwipedContainer.css({
                transform: 'translateX(0)',
                transition: 'all 200ms ease-out'
            });

            this.currentSwipedContainer.removeClass(this.swipeDoneClassName);
            this.currentSwipedContainer.removeClass('swipe-active');
        }
    });

    return ElasticSwipeActions;
});
