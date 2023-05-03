define(function(require) {
    'use strict';

    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const viewportManager = require('oroui/js/viewport-manager').default;
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const _ = require('underscore');

    require('jquery.mousewheel');
    require('styled-scroll-bar');

    const StickedScrollbarPlugin = BasePlugin.extend({
        viewport: 'all',

        domCache: null,

        /**
         * @inheritdoc
         */
        initialize: function(grid, options) {
            _.extend(this, _.pick(options || {}, ['viewport']));
            this.grid = grid;

            this.listenToOnce(this.grid, 'shown', this.enable);
            this.onViewportChange = this.onViewportChange.bind(this);
            // add the event handler, that won't be removed through disable action
            // (has to be removed only in dispose)
            mediator.on(`viewport:${this.viewport}`, this.onViewportChange);

            return StickedScrollbarPlugin.__super__.initialize.call(this, grid, options);
        },

        /**
         * @inheritdoc
         */
        eventNamespace: function() {
            return StickedScrollbarPlugin.__super__.eventNamespace.call(this) + '.stickedScrollbar';
        },

        /**
         * @inheritdoc
         */
        enable: function() {
            if (this.enabled || !this.grid.rendered || !viewportManager.isApplicable(this.viewport)) {
                return;
            }

            this.setupDomCache();

            this.domCache.$container.styledScrollBar({
                overflowBehavior: {
                    y: 'hidden'
                },
                callbacks: {
                    onScroll: _.debounce(function(event) {
                        this.domCache.$container.trigger('updateScroll', event);
                    }.bind(this), 5)
                }
            });
            this.domCache.$scrollbar = $(this.domCache.$container
                .styledScrollBar('getElements').scrollbarHorizontal.scrollbar);

            this.delegateEvents();

            const displayScrollbar = this.checkScrollbarDisplay();

            this.domCache.$container.styledScrollBar(displayScrollbar ? 'update' : 'sleep');

            StickedScrollbarPlugin.__super__.enable.call(this);
        },

        /**
         * @inheritdoc
         */
        disable: function() {
            if (!this.enabled) {
                return;
            }

            this.undelegateEvents();
            this.domCache.$container.styledScrollBar('dispose');

            return StickedScrollbarPlugin.__super__.disable.call(this);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disable();
            delete this.domCache;
            mediator.off(`viewport:${this.viewport}`, this.onViewportChange);

            return StickedScrollbarPlugin.__super__.dispose.call(this);
        },

        setupDomCache: function() {
            this.domCache = {
                $window: $(window),
                $document: $(document),
                $grid: this.grid.$grid,
                $scrollableContainer: this.grid.$grid.closest('.scrollable-container'),
                $container: this.grid.$grid.parents('.grid-scrollable-container:first'),
                $spyScroll: this.grid.$grid.parents('[data-spy="scroll"]:first'),
                $oroTabs: this.grid.$grid.parents('.oro-tabs:first'),
                $collapsible: this.grid.$grid.parents('.collapse:first'),
                $thead: this.grid.$grid.find('thead:first')
            };
        },

        delegateEvents: function() {
            const manageScroll = this.manageScroll.bind(this);
            const updateCustomScrollbar = _.debounce(this.updateCustomScrollbar.bind(this), 50);

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
            this.domCache.$scrollableContainer.on('scroll' + this.eventNamespace(), _.throttle(manageScroll, 100));
            this.domCache.$window.on('resize' + this.eventNamespace(), updateCustomScrollbar);
            this.domCache.$oroTabs.on('shown' + this.eventNamespace(), updateCustomScrollbar);

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
            const $grid = this.domCache.$grid;
            const $container = this.domCache.$container;
            let display = $grid.width() > $container.width();

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
            const containerOffsetTop = this.domCache.$container.offset().top;
            const containerHeight = this.domCache.$container.height();
            const windowHeight = this.domCache.$window.height();
            const windowScrollTop = this.domCache.$window.scrollTop();
            const tHeadHeight = this.domCache.$thead.height();
            const scrollBarHeight = this.domCache.$scrollbar.height();

            const viewportTop = containerOffsetTop - windowScrollTop;
            const viewportBottom = windowHeight - viewportTop - containerHeight;
            const viewportLowLevel = windowHeight + windowScrollTop - tHeadHeight - scrollBarHeight;

            return viewportBottom > 0 || viewportLowLevel < containerOffsetTop;
        },

        attachScrollbar: function() {
            this.domCache.$scrollbar.removeAttr('style');
        },

        detachScrollbar: function() {
            const $scrollbar = this.domCache.$scrollbar;
            const containerWidth = this.domCache.$container.width();
            const containerLeftOffset = this.domCache.$container.offset().left;
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
            if (this.domCache.$container.data('oro.styledScrollBar')) {
                this.domCache.$container.styledScrollBar('update');
            }
        },

        onGridHeaderCellWidthBeforeUpdate: function() {
            if (this.domCache.$container.data('oro.styledScrollBar')) {
                this.domCache.$container.styledScrollBar('update');
            }
        },

        onViewportChange: function(e) {
            if (e.matches) {
                this.enable();
            } else {
                this.disable();
            }
        }
    });

    return StickedScrollbarPlugin;
});
