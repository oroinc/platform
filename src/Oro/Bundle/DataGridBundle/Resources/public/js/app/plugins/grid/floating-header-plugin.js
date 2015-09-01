define(function(require) {
    'use strict';

    var FloatingHeaderPlugin;
    var $ = require('jquery');
    var _ = require('underscore');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var Backbone = require('backbone');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var tools = require('oroui/js/tools');

    FloatingHeaderPlugin = BasePlugin.extend({
        initialize: function(grid) {
            this.grid = grid;
            this.grid.on('shown', _.bind(this.onGridShown, this));

            this.selectMode = _.bind(this.selectMode, this);
            this.checkLayout = _.bind(this.checkLayout, this);
            this.fixHeaderCellWidth = _.bind(this.fixHeaderCellWidth, this);
        },

        onGridShown: function() {
            if (this.enabled && !this.connected) {
                this.enable();
            }
        },

        enable: function() {
            if (!this.grid.rendered) {
                // not ready to apply floatingHeader
                FloatingHeaderPlugin.__super__.enable.call(this);
                return;
            }

            this.setupCache();
            this.rescrollCb = this.enableOtherScroll();
            this.headerHeight = this.domCache.theadTr.height();
            this.fixHeaderCellWidth();
            this.$grid.on('click.float-thead', 'thead:first .dropdown', _.bind(function() {
                this.setFloatTheadMode(this.scrollVisible ? 'relative' : 'default');
            }, this));
            this.domCache.gridContainer.parents().add(document).on('scroll', this.checkLayout);

            this.listenTo(mediator, 'layout:headerStateChange', this.selectMode);
            this.listenTo(this.grid, 'content:update', this.fixHeaderCellWidth);
            this.listenTo(this.grid, 'layout:update', this.fixHeaderCellWidth);
            this.listenTo(this.grid.columns, 'change:renderable', this.onGridHeaderChange);
            this.listenTo(this.grid.header.row, 'content:update', this.onGridHeaderChange);
            this.checkLayoutIntervalId = setInterval(this.checkLayout, 400);
            this.connected = true;
            FloatingHeaderPlugin.__super__.enable.call(this);
        },

        disable: function() {
            this.connected = false;
            clearInterval(this.checkLayoutIntervalId);
            this.checkLayout();

            this.setFloatTheadMode('default');
            this.disableOtherScroll();
            this.$grid.off('click.float-thead');
            this.domCache.gridContainer.parents().add(document).off('scroll', this.checkLayout);
            // remove css
            this.domCache.headerCells.attr('style', '');
            this.domCache.firstRowCells.attr('style', '');
            FloatingHeaderPlugin.__super__.disable.call(this);
        },

        setupCache: function() {
            this.$grid = this.grid.$grid;
            this.$el = this.grid.$el;
            this.documentHeight = $(document).height();
            this.domCache = {
                body: $(document.body),
                gridContainer: this.$grid.parent(),
                headerCells: this.$grid.find('th:first').parent().find('th'),
                firstRowCells: this.$grid.find('tbody tr:not(.thead-sizing-row):first').children('td'),
                otherScrollContainer: this.$grid.parents('.other-scroll-container:first'),
                gridScrollableContainer: this.$grid.parents('.grid-scrollable-container:first'),
                otherScroll: this.$el.find('.other-scroll'),
                otherScrollInner: this.$el.find('.other-scroll > div'),
                thead: this.$grid.find('thead:first'),
                theadTr: this.$grid.find('thead:first tr:first')
            };
        },

        fixHeaderCellWidth: function() {
            this.setupCache();
            var headerCells = this.domCache.headerCells;
            var firstRowCells = this.domCache.firstRowCells;
            var totalWidth;
            var sumWidth;
            var widthDecrement = 0;
            var widths = [];
            var self = this;
            var scrollBarWidth = mediator.execute('layout:scrollbarWidth');
            // remove style
            headerCells.attr('style', '');
            firstRowCells.attr('style', '');
            this.$grid.css({width: ''});
            this.domCache.gridContainer.css({width: ''});
            this.$el.removeClass('floatThead');

            // compensate scroll bar
            if (this.scrollVisible) {
                this.$grid.css({borderRight: scrollBarWidth + 'px solid transparent'});
                totalWidth = this.$grid[0].offsetWidth - scrollBarWidth;
            } else {
                totalWidth = this.$grid[0].offsetWidth;
            }

            // save widths
            headerCells.each(function(i, headerCell) {
                widths.push(headerCell.offsetWidth);
            });

            // FF sometimes gives wrong values, need to check
            sumWidth = _.reduce(widths, function(a, b) {return a + b;});

            if (sumWidth > totalWidth) {
                widthDecrement = (sumWidth - totalWidth) / widths.length + 0.001;
            }

            // add scroll bar width to last cell if scroll is visible
            if (self.scrollVisible) {
                widths[widths.length - 1] += scrollBarWidth;
                totalWidth += scrollBarWidth;
            }

            // set exact sizes to header cells and cells in first row
            headerCells.each(function(i, headerCell) {
                var cellWidth = widths[i] - widthDecrement;
                headerCell.style.width = cellWidth + 'px';
                headerCell.style.minWidth = cellWidth + 'px';
                headerCell.style.boxSizing = 'border-box';
                if (firstRowCells[i]) {
                    firstRowCells[i].style.width = cellWidth + 'px';
                    firstRowCells[i].style.minWidth = cellWidth + 'px';
                    firstRowCells[i].style.boxSizing = 'border-box';
                }
            });

            this.$grid.css({borderRight: 'none'});

            this.$el.addClass('floatThead');
            this.$grid.css({
                width: totalWidth
            });
            this.domCache.gridContainer.css({
                width: totalWidth
            });

            this.selectMode();
        },

        /**
         * Selects floating header mode
         */
        selectMode: function() {
            // get gridRect
            var tableRect = this.domCache.gridContainer[0].getBoundingClientRect();
            var visibleRect = this.getVisibleRect(this.domCache.gridContainer[0]);
            var mode = 'default';
            if (visibleRect.top !== tableRect.top || this.grid.layout === 'fullscreen') {
                mode = 'fixed';
            }
            this.setFloatTheadMode(mode, visibleRect, tableRect);

            // update tracked values to prevent calling this function again
            this._lastClientRect = this.domCache.otherScrollContainer[0].getBoundingClientRect();
            this._lastScrollLeft = this.domCache.gridScrollableContainer.scrollLeft();
            if (this.rescrollCb) {
                this.rescrollCb();
            }
        },

        /**
         * Setups floating header mode
         */
        setFloatTheadMode: function(mode, visibleRect, tableRect) {
            var theadRect;
            // pass this argument to avoid expensive calculations
            if (!visibleRect) {
                visibleRect = this.getVisibleRect(this.domCache.gridContainer[0]);
            }
            if (!tableRect) {
                tableRect = this.domCache.gridContainer[0].getBoundingClientRect();
            }
            switch (mode) {
                case 'relative':
                    // works well with dropdowns, but causes jumps while scrolling
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-fixed');
                        this.$el.addClass('floatThead-relative');
                        this._ensureTHeadSizing();
                    }
                    theadRect = this.domCache.thead[0].getBoundingClientRect();
                    this.domCache.thead.css({
                        width: '',
                        top: visibleRect.top - tableRect.top
                    });
                    this.domCache.theadTr.css({
                        marginLeft: tableRect.left - theadRect.left
                    });
                    if (mode === 'relative') {
                        this._lastScrollTop = this.domCache.gridScrollableContainer.scrollTop();
                    }
                    break;
                case 'fixed':
                    // provides good scroll experience
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-relative');
                        this.$el.addClass('floatThead-fixed');
                        this.$grid.find('thead:first .dropdown.open').removeClass('open');
                        this._ensureTHeadSizing();
                    }
                    this.domCache.thead.css({
                        // show only visible part
                        top: visibleRect.top,
                        width: visibleRect.right - visibleRect.left,
                        height: Math.min(this.headerHeight, visibleRect.bottom - visibleRect.top),

                        // left side should be also tracked
                        // gives incorrect rendering when "document" scrolled horizontally
                        left: visibleRect.left
                    });
                    theadRect = this.domCache.thead[0].getBoundingClientRect();
                    this.domCache.theadTr.css({
                        // possible solution set scrollLeft instead
                        // could be more fast for rendering
                        marginLeft: tableRect.left - theadRect.left
                    });
                    break;
                default:
                    if (this.currentFloatTheadMode !== mode) {
                        this.$grid.find('.thead-sizing').remove();
                        this.$el.removeClass('floatThead-relative floatThead-fixed');
                        // remove extra styles
                        this.domCache.thead.attr('style', '');
                        this.domCache.theadTr.attr('style', '');
                        // cleanup
                    }
                    break;
            }
            this.currentFloatTheadMode = mode;
        },

        /**
         * Handles grid head changes
         * (hiding/showing and sorting columns)
         */
        onGridHeaderChange: function() {
            this.$grid.find('.thead-sizing').remove();
            this._ensureTHeadSizing();
            this.fixHeaderCellWidth();
        },

        /**
         * Creates thead clone if it does not exist
         *
         * @protected
         */
        _ensureTHeadSizing: function() {
            if (!this.$grid.find('.thead-sizing').length) {
                var sizingThead = this.domCache.thead.clone();
                sizingThead.addClass('thead-sizing');
                sizingThead.find('th').attr('style', '');
                sizingThead.insertAfter(this.domCache.thead);
            }
        },

        /**
         * Enables other scroll functionality
         */
        enableOtherScroll: function() {
            var heightDec;
            var self = this;
            var scrollContainer = this.domCache.gridScrollableContainer;
            var otherScroll = this.domCache.otherScroll;
            var otherScrollInner = this.domCache.otherScrollInner;
            var scrollBarWidth = mediator.execute('layout:scrollbarWidth');
            var scrollStateModel = new Backbone.Model();

            this.scrollStateModel = scrollStateModel;

            if (scrollBarWidth === 0) {
                // nothing to do
                return _.noop;
            }

            scrollStateModel.on('change:headerHeight', function(model, val) {
                heightDec = val + 1; // compensate border
                otherScroll.css({
                    width: scrollBarWidth,
                    marginTop: heightDec
                });
                scrollStateModel.trigger('change:scrollHeight', scrollStateModel, scrollContainer[0].scrollHeight);
                scrollStateModel.trigger('change:clientHeight', scrollStateModel, scrollContainer[0].clientHeight);
            }, this);
            scrollStateModel.on('change:visible', function(model, val) {
                scrollContainer.css({
                    width: 'calc(100% + ' + (val ? scrollBarWidth : 0) + 'px)'
                });
                otherScroll.css({
                    display: val ? 'block' : 'none'
                });
                scrollContainer.toggleClass('scrollbar-is-visible', Boolean(val));
                this.fixHeaderCellWidth();
            }, this);
            scrollStateModel.on('change:clientHeight', function(model, val) {
                otherScroll.css({
                    height: val - heightDec
                });
            }, this);
            scrollStateModel.on('change:clientWidth', function(model, val) {
                otherScroll.css({
                    marginLeft: val - scrollBarWidth
                });
            }, this);
            scrollStateModel.on('change:scrollHeight', function(model, val) {
                otherScrollInner.css({
                    height: val - heightDec
                });
            });
            scrollStateModel.on('change:scrollTop', function(model, val) {
                if (otherScroll[0].scrollTop !== val) {
                    otherScroll[0].scrollTop = val;
                }
                if (scrollContainer[0].scrollTop !== val) {
                    scrollContainer[0].scrollTop = val;
                }
            }, this);

            function updateScroll(e) {
                scrollStateModel.set({
                    scrollTop: e.currentTarget.scrollTop
                });
            }

            scrollContainer.on('scroll', updateScroll);

            otherScroll.on('scroll', updateScroll);

            function setup() {
                scrollStateModel.set({
                    headerHeight: self.headerHeight
                });

                self.scrollVisible = scrollContainer[0].clientHeight < scrollContainer[0].scrollHeight;
                scrollStateModel.set({
                    visible: self.scrollVisible,
                    scrollHeight:  scrollContainer[0].scrollHeight,
                    clientHeight:  scrollContainer[0].clientHeight,
                    clientWidth:   scrollContainer[0].clientWidth,
                    scrollTop:     scrollContainer[0].scrollTop
                });
            }

            setup();
            return setup;
        },

        /**
         * Disables other scroll functionality
         */
        disableOtherScroll: function() {
            this.domCache.gridScrollableContainer.off('scroll', this.rescrollCb);
            this.domCache.otherScroll.off('scroll');
            this.domCache.otherScroll.css({display: 'none'});
            this.domCache.gridScrollableContainer.css({width: ''}).removeClass('scrollbar-is-visible');
            this.domCache.gridContainer.css({width: ''});
            this.$grid.css({width: ''});
            this.scrollStateModel.destroy();
            delete this.scrollStateModel;
            delete this.rescrollCb;
        },

        /**
         * Checks and performs required actions
         */
        checkLayout: function() {
            var scrollContainerRect;
            var scrollLeft;
            if (this.currentFloatTheadMode === 'default') {
                if (this.grid.layout === 'fullscreen' &&
                        this.currentFloatTheadMode === 'default' &&
                        this.domCache.gridScrollableContainer.scrollTop() !== 0) {
                    this.selectMode();
                    return;
                }
            }
            if (this.currentFloatTheadMode === 'relative' &&
                    this.domCache.gridScrollableContainer.scrollTop() !== this._lastScrollTop) {
                this.selectMode();
                return;
            }
            scrollContainerRect = this.domCache.otherScrollContainer[0].getBoundingClientRect();
            if (!this._lastClientRect || (this._lastClientRect.top !== scrollContainerRect.top ||
                    this._lastClientRect.left !== scrollContainerRect.left ||
                    this._lastClientRect.right !== scrollContainerRect.right)) {
                if (!this._lastClientRect || (this._lastClientRect.left !== scrollContainerRect.left ||
                        this._lastClientRect.right !== scrollContainerRect.right)) {
                    this.fixHeaderCellWidth();
                } else {
                    this.selectMode();
                }
            } else {
                scrollLeft = this.domCache.gridScrollableContainer.scrollLeft();
                if (this._lastScrollLeft !== scrollLeft) {
                    this.selectMode();
                    this._lastScrollLeft = scrollLeft;
                }
            }
            this._lastClientRect = scrollContainerRect;
        },

        /**
         * Returns visible rect of DOM element
         *
         * @param el
         * @returns {{top: number, left: Number, bottom: Number, right: Number}}
         */
        getVisibleRect: function(el) {
            var current = el;
            var midRect = current.getBoundingClientRect();
            var borders;
            var resultRect = {
                top: midRect.top - this.headerHeight,
                left: midRect.left,
                bottom: midRect.bottom,
                right: midRect.right
            };
            if (
                (resultRect.top === 0 && resultRect.bottom === 0) || // no-data block is shown
                (resultRect.top > this.documentHeight && this.currentFloatTheadMode === 'default') // grid is invisible
            ) {
                // no need to calculate anything
                return resultRect;
            }
            current = current.parentNode;
            while (current && current.getBoundingClientRect) {
                midRect = current.getBoundingClientRect();
                borders = $.fn.getBorders(current);

                if (tools.isMobile()) {
                    /**
                     * Equals header height. Cannot calculate dynamically due to issues on ipad
                     */
                    if (resultRect.top < layout.MOBILE_HEADER_HEIGHT && current.id === 'top-page' &&
                        !this.domCache.body.hasClass('input-focused')) {
                        resultRect.top = layout.MOBILE_HEADER_HEIGHT;
                    } else if (resultRect.top < layout.MOBILE_POPUP_HEADER_HEIGHT &&
                        current.className === 'widget-content') {
                        resultRect.top = layout.MOBILE_POPUP_HEADER_HEIGHT;
                    }
                }

                if (resultRect.top < midRect.top + borders.top) {
                    resultRect.top = midRect.top + borders.top;
                }
                if (resultRect.bottom > midRect.bottom - borders.bottom) {
                    resultRect.bottom = midRect.bottom - borders.bottom;
                }
                if (resultRect.left < midRect.left + borders.left) {
                    resultRect.left = midRect.left + borders.left;
                }
                if (resultRect.right > midRect.right - borders.right) {
                    resultRect.right = midRect.right - borders.right;
                }
                current = current.parentNode;
            }

            if (resultRect.top < 0) {
                resultRect.top = 0;
            }

            return resultRect;
        }
    });

    return FloatingHeaderPlugin;
});
