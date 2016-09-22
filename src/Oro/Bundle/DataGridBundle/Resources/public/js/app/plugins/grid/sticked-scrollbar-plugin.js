define(function(require) {
    'use strict';

    var StickedScrollbarPlugin;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var $ = require('jquery');
    var _ = require('underscore');
    require('mCustomScrollbar');
    require('jquery.mousewheel');

    StickedScrollbarPlugin = BasePlugin.extend({
        /**
         * mCustomScrollbar initialization options
         * @type {Object}
         */
        mcsOptions: {
            axis: 'x',
            contentTouchScroll: 10,
            documentTouchScroll: true,
            theme: 'dark',
            advanced: {
                autoExpandHorizontalScroll: true,
                updateOnContentResize: false,
                updateOnImageLoad: false
            }
        },

        domCache: null,

        timeouts: null,

        scrollState: null,

        viewport: null,

        /**
         * @inheritDoc
         */
        initialize: function(grid) {
            this.grid = grid;

            this.grid.on('shown', _.bind(this.onGridShown, this));
            this.grid.on('content:update', _.bind(this.updateCustomScrollbar, this));
        },

        onGridShown: function() {
            if (this.enabled && !this.connected) {
                this.enable();
            }
        },

        /**
         * @inheritDoc
         */
        enable: function() {
            if (!this.grid.rendered) {
                // not ready to apply stickedScrollbar
                StickedScrollbarPlugin.__super__.enable.call(this);
                return;
            }

            _.extend(this.mcsOptions, {
                callbacks: {
                    onCreate: _.bind(this.onCreate, this),
                    onOverflowX: _.bind(this.onOverflowX, this),
                    onOverflowXNone: _.bind(this.onOverflowXNone, this)
                }
            });

            this.setupCache();
            this.setupEvents();
            this.enableCustomScrollbar();

            this.connected = true;
            StickedScrollbarPlugin.__super__.enable.call(this);
        },

        /**
         * @inheritDoc
         */
        disable: function() {
            this.connected = false;
            this.domCache.$container.mCustomScrollbar('destroy');
            StickedScrollbarPlugin.__super__.disable.call(this);
        },

        setupCache: function() {
            this.domCache = {
                $window: $(window),
                $document: $(document),
                $container: this.grid.$grid.parents('.grid-scrollable-container:first'),
                $thead: this.grid.$grid.find('thead:first')
            };
            this.timeouts = {
                resizeTimeout: 50,
                scrollTimeout: 40
            };
            this.scrollState = {
                display: true,
                state: 'attached'
            };
            this.viewport = {
                top: 0,
                bottom: 0,
                lowLevel: 0
            };
        },

        setupEvents: function() {
            this.domCache.$document.on('scroll', _.debounce(_.bind(this.manageScroll, this), this.timeouts.scrollTimeout));
            this.domCache.$window.on('resize', _.debounce(_.bind(this.updateCustomScrollbar, this), this.timeouts.resizeTimeout));
        },

        onOverflowX: function() {
            this.scrollState.display = true;
        },

        onOverflowXNone: function() {
            this.scrollState.display = false;
        },

        onCreate: function() {
            this.manageScroll();
        },

        manageScroll: function() {
            this.updateViewport();

            if (this.viewport.bottom <= 0 &&
                this.viewport.lowLevel >= this.domCache.$container.offset().top &&
                this.scrollState.display) {
                this.detachScrollbar();
            } else if (this.scrollState.display) {
                this.attachScrollbar();
            }
        },

        updateViewport: function() {
            var $scrollbar = this.domCache.$container.find('.mCSB_scrollTools');
            this.viewport.top = this.domCache.$container.offset().top - this.domCache.$window.scrollTop();
            this.viewport.bottom = this.domCache.$window.height() - this.viewport.top - this.domCache.$container.height();
            this.viewport.lowLevel = this.domCache.$window.height() + this.domCache.$window.scrollTop() - this.domCache.$thead.height() - $scrollbar.height();
        },

        enableCustomScrollbar: function() {
            this.domCache.$container.mCustomScrollbar(this.mcsOptions);
        },

        updateCustomScrollbar: function() {
            this.manageScroll();
            this.domCache.$container.mCustomScrollbar('update');
        },

        attachScrollbar: function() {
            var $scrollbar = this.domCache.$container.find('.mCSB_scrollTools');
            $scrollbar.removeAttr('style');

            if (!this.scrollState.display) {
                $scrollbar.css('display', 'none');
            }

            this.scrollState.state = 'attached';
        },

        detachScrollbar: function() {
            var $scrollbar = this.domCache.$container.find('.mCSB_scrollTools');
            var containerWidth = this.domCache.$container.width();
            var containerLeftOffset = this.domCache.$container.offset().left;
            var documentWidth = this.domCache.$document.width();

            $scrollbar.css({
                'position': 'fixed',
                'top': 'auto',
                'right': documentWidth - containerWidth - containerLeftOffset + 'px',
                'left': 'auto',
                'bottom': 0,
                'z-index': 999,
                'width': containerWidth + 'px'
            });

            this.scrollState.state = 'detached';
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            this.domCache.$document.off('scroll', _.bind(this.manageScroll, this));
            this.domCache.$window.off('resize', _.bind(this.updateCustomScrollbar, this));

            _.each(['domCache', 'timeouts', 'scrollState', 'viewport'], function(key) {
                delete this[key];
            }, this);

            StickedScrollbarPlugin.__super__.dispose.call(this);
        }
    });

    return StickedScrollbarPlugin;
});
