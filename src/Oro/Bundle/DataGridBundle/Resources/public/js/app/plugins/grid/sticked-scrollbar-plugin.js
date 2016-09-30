define(function(require) {
    'use strict';

    var StickedScrollbarPlugin;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var mediator = require('oroui/js/mediator');
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
            theme: 'inset-dark',
            advanced: {
                autoExpandHorizontalScroll: 3,
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

            this.setupCache();
            this.setupEvents();
            this.enableCustomScrollbar();

            this.listenTo(mediator, 'layout:reposition', this.updateCustomScrollbar);
            this.listenTo(mediator, 'gridHeaderCellWidth:updated', this.updateCustomScrollbar);
            this.listenTo(this.grid, 'content:update', this.updateCustomScrollbar);

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
            this.setupDomCache();
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

        setupDomCache: function() {
            this.domCache = {
                $window: $(window),
                $document: $(document),
                $grid: this.grid.$grid,
                $container: this.grid.$grid.parents('.grid-scrollable-container:first'),
                $scrollbar: this.grid.$grid.find('.mCSB_scrollTools'),
                $thead: this.grid.$grid.find('thead:first')
            };
        },

        setupEvents: function() {
            this.domCache.$document.on('scroll',
                _.debounce(_.bind(this.manageScroll, this), this.timeouts.scrollTimeout)
            );
            this.domCache.$window.on('resize',
                _.debounce(_.bind(this.updateCustomScrollbar, this), this.timeouts.resizeTimeout)
            );
        },

        manageScroll: function() {
            if (!this.domCache) {
                return;
            }

            this.detectScrollbar();
            this.updateViewport();

            if (this.viewport.bottom <= 0 &&
                this.viewport.lowLevel >= this.domCache.$container.offset().top &&
                this.scrollState.display) {
                this.detachScrollbar();
            } else if (this.scrollState.display) {
                this.attachScrollbar();
            }
        },

        detectScrollbar: function() {
            var $grid = this.domCache.$grid;
            var $container = this.domCache.$container;
            return this.scrollState.display = $grid.width() > $container.width();
        },

        updateViewport: function() {
            if (!this.scrollState.display) {
                return;
            }

            this.viewport.top = this.domCache.$container.offset().top - this.domCache.$window.scrollTop();
            this.viewport.bottom = this.domCache.$window.height() - this.viewport.top - this.domCache.$container.height();
            this.viewport.lowLevel = this.domCache.$window.height() + this.domCache.$window.scrollTop() - this.domCache.$thead.height() - this.domCache.$scrollbar.height();
        },

        enableCustomScrollbar: function() {
            this.domCache.$container.mCustomScrollbar(this.mcsOptions);
        },

        updateCustomScrollbar: function() {
            if (!this.domCache) {
                return;
            }

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
            if (this.disposed) {
                return;
            }

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
