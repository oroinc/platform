define(function(require) {
    'use strict';

    var ScrollView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var _ = require('underscore');
    require('jquery.mousewheel');
    require('jquery.mCustomScrollbar');

    ScrollView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            scrollableContainer: '[data-scroll-view]',
            followXAxis: '[data-scroll-view-follow="x"]',
            followYAxis: '[data-scroll-view-follow="y"]'
        },

        /**
         * Initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            ScrollView.__super__.initialize.apply(this, options);
            this.initializeElements(options);
            this.initScrollContainer();
        },

        /**
         * Init scroll container
         */
        initScrollContainer: function() {
            this.setStartPosition();

            this.getElement('scrollableContainer').on('scroll mousewheel', _.bind(function(e) {
                e.stopPropagation();
                this.updateFollowersPosition(e.currentTarget);
            }, this));
        },

        updateFollowersPosition: function(element) {
            this._transformFollowers('x', -element.scrollLeft);
            this._transformFollowers('y', -element.scrollTop);
        },

        setStartPosition: function() {
            this.updateFollowersPosition(this.getElement('scrollableContainer').get(0));
        },

        _transformFollowers: function(direction, value) {
            this.getElement('follow' + direction.toUpperCase() + 'Axis').css({
                transform: 'translate' + direction.toUpperCase() + '(' + value + 'px)'
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getElement('scrollableContainer').off();

            ScrollView.__super__.dispose.call(this);
        }
    }));

    return ScrollView;
});
