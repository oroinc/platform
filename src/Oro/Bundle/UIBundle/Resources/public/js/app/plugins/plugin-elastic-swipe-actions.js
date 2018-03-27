define(function(require) {
    'use strict';

    var ElasticSwipeActions;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var ViewportManager = require('oroui/js/viewport-manager');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    /**
     * Elastic swipe actions plugin for frontend grid
     *
     * @class
     * @augments BasePlugin
     * @exports ElasticSwipeActions
     */
    ElasticSwipeActions = BasePlugin.extend({
        /**
         * Current swiped element container
         * @property {jQuery}
         */
        currentSwipedContainer: null,

        /**
         * Selector for find swiped container
         * @property {String}
         */
        containerSelector: null,

        /**
         * Control point for moving to the end position
         * @property {Number}
         */
        breakPointPosition: 50,

        /**
         * Limit of end point
         * @property {Number}
         */
        maxLimit: 175,

        /**
         * On done CSS classname
         * @property {String}
         */
        swipeDoneClassName: 'swipe-done',

        /**
         * On active CSS classname
         * @property {String}
         */
        swipeActionClassName: 'swipe-active',

        /**
         * Out of the limit
         * @property {Boolean}
         */
        elastic: false,

        /**
         * Save end point position
         * @property {Number}
         */
        storedPos: 0,

        /**
         * Dynamic size for container offset
         * @property {String}
         */
        sizerSelector: null,

        /**
         * Viewport manager options
         * @property {Object}
         */
        viewport: {
            minScreenType: 'any'
        },

        enabled: false,

        /**
         * @Initialize
         *
         * @param {Object} grid
         * @param {Object} options
         * @returns {*}
         */
        initialize: function(grid, options) {
            _.extend(this, _.pick(options || {},
                [
                    'containerSelector', 'breakPointPosition', 'maxLimit',
                    'swipeDoneClassName', 'elastic', 'viewport', 'sizerSelector'
                ]
            ));

            this.grid = grid;
            mediator.on('viewport:change', this.onViewportChange, this);

            return ElasticSwipeActions.__super__.initialize.apply(this, arguments);
        },

        /**
         * Is applicable viewport
         *
         * @param viewport
         */
        isApplicable: function(viewport) {
            return viewport.isApplicable(this.viewport);
        },

        /**
         * Enable swipe handler
         */
        enable: function() {
            if (this.enabled || !this.isApplicable(ViewportManager.getViewport())) {
                return;
            }

            this._bindEvents();
            return ElasticSwipeActions.__super__.enable.apply(this, arguments);
        },
        /**
         * Disable swipe handler
         */
        disable: function() {
            this._revertState();
            this._unbindEvents();

            delete this.currentSwipedContainer;

            return ElasticSwipeActions.__super__.disable.apply(this, arguments);
        },

        /**
         * Destroy swipe handler
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disable();

            return ElasticSwipeActions.__super__.dispose.apply(this, arguments);
        },

        /**
         * Listen responsive changes
         *
         * @param {Object} viewport
         */
        onViewportChange: function(viewport) {
            if (this.isApplicable(viewport)) {
                this.enable();
            } else {
                this.disable();
            }
        },

        /**
         * Apply dynamic offset for swiping container
         *
         * @param {jQuery} container
         * @private
         */
        _applyDynamicOffset: function(container) {
            var sizer = container.find(this.sizerSelector);

            if (!sizer.length) {
                return;
            }

            var size = container.find(this.sizerSelector).outerWidth();

            this.maxLimit = size;
            this.breakPointPosition = size / 2;
            container.css({
                paddingRight: size,
                marginRight: -size
            });
        },

        /**
         * Set touch swipe event handlers
         *
         * @private
         */
        _bindEvents: function() {
            mediator.on('swipe-action-start', this._onStart, this);
            mediator.on('swipe-action-move', this._onMove, this);
            mediator.on('swipe-action-end', this._onEnd, this);
        },

        /**
         * Remove touch swipe event handlers
         *
         * @private
         */
        _unbindEvents: function() {
            mediator.off('swipe-action-start', this._onStart, this);
            mediator.off('swipe-action-move', this._onMove, this);
            mediator.off('swipe-action-end', this._onEnd, this);
        },

        /**
         * On start swipe action functionality
         *
         * @param {Object} data
         * @param {DOM.element} target
         * @private
         */
        _onStart: function(data, target) {
            var container = $(target).closest(this.containerSelector);

            if (this.sizerSelector) {
                this._applyDynamicOffset(container);
            }

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

            this.currentSwipedContainer.addClass(this.swipeActionClassName);
        },

        /**
         * On move swipe action functionality
         *
         * @param {Object} data
         * @private
         */
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

        /**
         * On end of swipe action functionality
         *
         * @param {Object} data
         * @private
         */
        _onEnd: function(data) {
            var xAxe = data.x - this.storedPos;
            if (Math.abs(xAxe) < this.breakPointPosition) {
                this._revertState();
                return;
            }

            if (
                (data.direction === 'left' && Math.abs(xAxe) > this.breakPointPosition) ||
                (data.direction === 'right' && Math.abs(xAxe) > this.breakPointPosition && this.storedPos > 0)
            ) {
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

        /**
         * Reset container state
         *
         * @private
         */
        _revertState: function() {
            if (!this.enabled) {
                return;
            }

            this.currentSwipedContainer.data('offset', 0);
            this.storedPos = 0;
            this.currentSwipedContainer.css({
                transform: 'translateX(0)',
                transition: 'all 200ms ease-out'
            });

            this.currentSwipedContainer.removeClass(this.swipeDoneClassName);
            this.currentSwipedContainer.removeClass(this.swipeActionClassName);
        }
    });

    return ElasticSwipeActions;
});
