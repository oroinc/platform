define(function(require) {
    'use strict';

    var StickedScrollbarPlugin;
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var viewportManager = require('oroui/js/viewport-manager');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');
    var PLUGIN_PFX = 'mCS';

    function onBeforeCustomScrollUpdate() {
        var instance = $(this).data(PLUGIN_PFX);
        var options = instance.opt;
        var mCustomScrollBox = $('#mCSB_' + instance.idx);
        var container = $('#mCSB_' + instance.idx + '_container');

        // Logic is copied from libs method `_expandContentHorizontally` to take in account width changing during update
        if (options.advanced.autoExpandHorizontalScroll && options.axis !== 'y') {
            container.css({
                'width': 'auto',
                'min-width': 0,
                'overflow-x': 'scroll'
            });

            var w = Math.ceil(container[0].scrollWidth);

            if (
                options.advanced.autoExpandHorizontalScroll === 3 ||
                options.advanced.autoExpandHorizontalScroll !== 2 && w > container.parent().width()
            ) {
                container.css({
                    'width': w,
                    'min-width': '100%',
                    'overflow-x': 'inherit'
                });
            }
        }

        var contentWidth = instance.overflowed == null ? container.width() : container.outerWidth(false);

        contentWidth = Math.max(contentWidth, container[0].scrollWidth);

        var difference = contentWidth - mCustomScrollBox.width();

        // Fix to avoid unnecessary showing of scrollbar when mCustomScrollBox has fractional width
        if (difference < 1 && difference > 0) {
            mCustomScrollBox.css({
                'max-width': 'none',
                'min-width': 0,
                'width': contentWidth
            });
        }
    }

    function onCustomScrollUpdate() {
        var instance = $(this).data(PLUGIN_PFX);
        var mCustomScrollBox = $('#mCSB_' + instance.idx);

        mCustomScrollBox.css({
            'max-width': '',
            'min-width': '',
            'width': ''
        });
    }

    require('jquery.mCustomScrollbar');
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
            },
            callbacks: {
                onBeforeUpdate: onBeforeCustomScrollUpdate,
                onUpdate: onCustomScrollUpdate
            }
        },

        viewport: {
            minScreenType: 'any'
        },

        domCache: null,

        /**
         * @inheritDoc
         */
        initialize: function(grid, options) {
            _.extend(this, _.pick(options || {}, ['viewport']));
            this.grid = grid;
            this.listenTo(this.grid, 'shown', this.enable);
            mediator.on('viewport:change', this.onViewportChange, this);

            return StickedScrollbarPlugin.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        eventNamespace: function() {
            return StickedScrollbarPlugin.__super__.eventNamespace.apply(this, arguments) + '.stickedScrollbar';
        },

        /**
         * @inheritDoc
         */
        enable: function() {
            if (this.enabled || !this.grid.rendered || !this.isApplicable(viewportManager.getViewport())) {
                return;
            }

            this.setupDomCache();
            this.domCache.$container.mCustomScrollbar(this.mcsOptions);
            this.domCache.$scrollbar = this.domCache.$container.find('.mCSB_scrollTools');
            this.delegateEvents();

            StickedScrollbarPlugin.__super__.enable.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        disable: function() {
            if (!this.enabled) {
                return;
            }

            this.undelegateEvents();
            this.domCache.$container.mCustomScrollbar('destroy');

            return StickedScrollbarPlugin.__super__.disable.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disable();
            mediator.off('viewport:change', this.onViewportChange, this);
            delete this.domCache;

            return StickedScrollbarPlugin.__super__.dispose.apply(this, arguments);
        },

        setupDomCache: function() {
            this.domCache = {
                $window: $(window),
                $document: $(document),
                $grid: this.grid.$grid,
                $container: this.grid.$grid.parents('.grid-scrollable-container:first'),
                $spyScroll: this.grid.$grid.parents('[data-spy="scroll"]:first'),
                $oroTabs: this.grid.$grid.parents('.oro-tabs:first'),
                $collapsible: this.grid.$grid.parents('.collapse:first'),
                $thead: this.grid.$grid.find('thead:first')
            };
        },

        delegateEvents: function() {
            var manageScroll = _.bind(this.manageScroll, this);
            var updateCustomScrollbar = _.debounce(_.bind(this.updateCustomScrollbar, this), 50);

            /*
            * For cases, when layout has full screen container with own scrollbar and window doesn't have scrollbar
            */
            this.domCache.$spyScroll.on('scroll' + this.eventNamespace(), manageScroll);
            /*
            * For cases when grid is inside collapsible block
            */
            this.domCache.$collapsible.on('hidden' + this.eventNamespace(), manageScroll);
            this.domCache.$collapsible.on('shown' + this.eventNamespace(), manageScroll);
            this.domCache.$document.on('scroll' + this.eventNamespace(), manageScroll);
            this.domCache.$window.on('resize' + this.eventNamespace(), updateCustomScrollbar);
            this.domCache.$oroTabs.on('show' + this.eventNamespace(), updateCustomScrollbar);

            this.listenTo(mediator, 'layout:reposition', this.updateCustomScrollbar);
            this.listenTo(mediator, 'gridHeaderCellWidth:beforeUpdate', this.onGridHeaderCellWidthBeforeUpdate);
            this.listenTo(mediator, 'gridHeaderCellWidth:updated', this.updateCustomScrollbar);
            this.listenTo(this.grid, 'content:update', this.updateCustomScrollbar);
        },

        undelegateEvents: function() {
            _.each(this.domCache, function($element) {
                $element.off(this.eventNamespace());
            }, this);

            this.stopListening();
            // Need reenable event for wake up plugin
            mediator.on('viewport:change', this.onViewportChange, this);
        },

        manageScroll: function() {
            if (!this.checkScrollbarDisplay()) {
                return;
            }

            if (!this.inViewport()) {
                this.detachScrollbar();
            } else {
                this.attachScrollbar();
            }
        },

        checkScrollbarDisplay: function() {
            var $grid = this.domCache.$grid;
            var $container = this.domCache.$container;
            var display = $grid.width() > $container.width();

            if (display && this.isGridHiddenUnderCollapse()) {
                display = false;
                this.domCache.$scrollbar.css('display', 'none');
            }

            return display;
        },

        isGridHiddenUnderCollapse: function() {
            return _.some(this.domCache.$grid.parents(), function(el) {
                return $(el).height() === 0;
            });
        },

        inViewport: function() {
            var containerOffsetTop = this.domCache.$container.offset().top;
            var containerHeight = this.domCache.$container.height();
            var windowHeight = this.domCache.$window.height();
            var windowScrollTop = this.domCache.$window.scrollTop();
            var tHeadHeight = this.domCache.$thead.height();
            var scrollBarHeight = this.domCache.$scrollbar.height();

            var viewportTop = containerOffsetTop - windowScrollTop;
            var viewportBottom = windowHeight - viewportTop - containerHeight;
            var viewportLowLevel = windowHeight + windowScrollTop - tHeadHeight - scrollBarHeight;

            return viewportBottom > 0 || viewportLowLevel < containerOffsetTop;
        },

        isApplicable: function(viewport) {
            return viewport.isApplicable(this.viewport);
        },

        attachScrollbar: function() {
            this.domCache.$scrollbar.removeAttr('style');
        },

        detachScrollbar: function() {
            var $scrollbar = this.domCache.$scrollbar;
            var containerWidth = this.domCache.$container.width();
            var containerLeftOffset = this.domCache.$container.offset().left;
            $scrollbar.removeAttr('style');

            $scrollbar.css({
                'position': 'fixed',
                'top': 'auto',
                'right': 'auto',
                'left': containerLeftOffset + 'px',
                'bottom': 0,
                'z-index': 999,
                'width': containerWidth + 'px'
            });
        },

        updateCustomScrollbar: function() {
            this.manageScroll();
            this.domCache.$container.mCustomScrollbar('update', false, 3);
        },

        onGridHeaderCellWidthBeforeUpdate: function() {
            this.domCache.$grid.parents('.mCSB_container:first').css({width: ''});
        },

        onViewportChange: function(viewport) {
            if (this.isApplicable(viewport)) {
                this.enable();
            } else {
                this.disable();
            }
        }
    });

    return StickedScrollbarPlugin;
});
