define(function (require) {
    'use strict';
    var $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools');

    function FloatingHeader(grid) {
        this.grid = grid;
        this.$grid = grid.$grid;
        this.$el = grid.$el;

        this.reposition = _.bind(this.reposition, this);
    }

    FloatingHeader.prototype = {

        /**
         * Interval id of height fix check function
         * @private
         */
        heightFixIntervalId: null,

        enable: function () {
            this.setupCache();
            this.rescrollCb = this.rescroll();
            this.headerHeight = this.cachedEls.theadTr.height();
            this.fixHeaderCellWidth();
            this.$grid.on('click', 'thead:first .dropdown', _.bind(function () {
                this.setFloatTheadMode('relative');
            }, this));
            this.cachedEls.gridContainer.parents().add(document).on('scroll', this.reposition);

            mediator.on('layout:headerStateChange', this.reposition, this);
            this.grid.on('content:update', this.fixHeaderCellWidth, this);
            this.grid.on('layout:update', this.fixHeaderCellWidth, this);
            this.heightFixIntervalId = setInterval(_.bind(this.fixHeightInFloatTheadMode, this), 400);
        },

        disable: function () {
            clearInterval(this.heightFixIntervalId);
            mediator.off('layout:headerStateChange', this.reposition, this);
            this.grid.off('content:update', this.fixHeaderCellWidth, this);
            this.grid.off('layout:update', this.fixHeaderCellWidth, this);

            this.setFloatTheadMode('default');
            this.$grid.parents().add(document).off('scroll', this.reposition);
            // remove css
            this.cachedEls.headerCells.attr('style', '');
            this.cachedEls.firstRowCells.attr('style', '');
        },

        setupCache: function () {
            this.documentHeight = $(document).height();
            this.cachedEls = {
                body: $(document.body),
                gridContainer: this.$grid.parent(),
                headerCells: this.$grid.find('th:first').parent().find('th'),
                firstRowCells: this.$grid.find('tbody tr:not(.thead-sizing-row):first td'),
                otherScrollContainer: this.$grid.parents('.other-scroll-container'),
                thead: this.$grid.find('thead:first'),
                theadTr: this.$grid.find('thead:first tr:first')
            }
        },

        fixHeaderCellWidth: function () {
            this.setupCache();
            var headerCells = this.cachedEls.headerCells,
                firstRowCells = this.cachedEls.firstRowCells,
                totalWidth = 0,
                self = this,
                scrollBarWidth = mediator.execute('layout:scrollbarWidth');
            // remove style
            headerCells.attr('style', '');
            firstRowCells.attr('style', '');
            this.$grid.css({width: ''});
            this.cachedEls.gridContainer.css({width: ''});
            if (this.scrollVisible) {
                this.$grid.css({borderRight: scrollBarWidth + 'px solid darkblue'});
            }
            this.$el.removeClass('floatThead');

            // copy widths
            headerCells.each(function (i, headerCell) {
                var cellWidth = headerCell.offsetWidth;
                if (self.scrollVisible && i === headerCells.length - 1) {
                    cellWidth += scrollBarWidth;
                }
                totalWidth += cellWidth;
                headerCell.style.minWidth = headerCell.style.width = cellWidth + 'px';
                headerCell.style.boxSizing = 'border-box';
                if (firstRowCells[i]) {
                    firstRowCells[i].style.minWidth = firstRowCells[i].style.width = cellWidth + 'px';
                    firstRowCells[i].style.boxSizing = 'border-box';
                }
            });

            this.$grid.css({borderRight: 'none'});
            this.$el.addClass('floatThead');
            this.$grid.css({
                width: totalWidth
            });
            this.cachedEls.gridContainer.css({
                width: totalWidth
            });

            this.reposition();
        },

        reposition: function () {
            // get gridRect
            var tableRect = this.cachedEls.gridContainer[0].getBoundingClientRect(),
                visibleRect = this.getVisibleRect(this.cachedEls.gridContainer[0]),
                mode = 'default';
            if (visibleRect.top !== tableRect.top || this.layout === 'fullscreen') {
                mode = 'fixed';
            }
            this.setFloatTheadMode(mode, visibleRect, tableRect);
            // update lastClientRect to prevent calling this function again
            this.lastClientRect = this.cachedEls.otherScrollContainer[0].getBoundingClientRect();
            if (this.rescrollCb) {
                this.rescrollCb();
            }
        },

        setFloatTheadMode: function (mode, visibleRect, tableRect) {
            var theadRect, sizingThead;
            // pass this argument to avoid expensive calculations
            if (!visibleRect) {
                visibleRect = this.getVisibleRect(this.cachedEls.gridContainer[0]);
            }
            if (!tableRect) {
                tableRect = this.cachedEls.gridContainer[0].getBoundingClientRect();
            }
            switch (mode) {
                case 'relative':
                    // works well with dropdowns, but causes jumps while scrolling
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-fixed');
                        this.$el.addClass('floatThead-relative');
                        if (!this.$grid.find('.thead-sizing').length) {
                            sizingThead = this.cachedEls.thead.clone().addClass('thead-sizing');
                            sizingThead.find('th').attr('style', '');
                            sizingThead.insertAfter(this.cachedEls.thead);
                        }
                    }
                    this.cachedEls.thead.css({
                        top: visibleRect.top - tableRect.top
                    });
                    theadRect = this.cachedEls.thead[0].getBoundingClientRect();
                    this.cachedEls.theadTr.css({
                        marginLeft: tableRect.left - theadRect.left
                    });
                    break;
                case 'fixed':
                    // provides good scroll experience
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-relative');
                        this.$el.addClass('floatThead-fixed');
                        this.$grid.find('thead:first .dropdown.open').removeClass('open');
                        if (!this.$grid.find('.thead-sizing').length) {
                            sizingThead = this.cachedEls.thead.clone().addClass('thead-sizing');
                            sizingThead.find('th').attr('style', '');
                            sizingThead.insertAfter(this.cachedEls.thead);
                        }
                    }
                    this.cachedEls.thead.css({
                        // show only visible part
                        top: visibleRect.top,
                        width: visibleRect.right - visibleRect.left,
                        height: Math.min(this.headerHeight, visibleRect.bottom - visibleRect.top),

                        // left side should be also tracked
                        // gives incorrect rendering when "document" scrolled horizontally
                        left: visibleRect.left
                    });
                    theadRect = this.cachedEls.thead[0].getBoundingClientRect();
                    this.cachedEls.theadTr.css({
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
                        this.cachedEls.thead.attr('style', '');
                        this.cachedEls.theadTr.attr('style', '');
                        // cleanup
                    }
                    break;
            }
            this.currentFloatTheadMode = mode;
        },

        rescroll: function () {
            var self = this,
                scrollContainer = this.$el.find('.grid-scrollable-container'),
                otherScroll = this.$el.find('.other-scroll'),
                otherScrollInner = this.$el.find('.other-scroll > div'),
                scrollBarWidth = mediator.execute('layout:scrollbarWidth'),
                scrollStateModel = new Backbone.Model(),
                heightDec;

            if (scrollBarWidth === 0) {
                // nothing to do
                return _.noop;
            }

            scrollStateModel.on('change:headerHeight', function (model, val) {
                heightDec = val + 1; // compensate border
                otherScroll.css({
                    width: scrollBarWidth,
                    marginTop: heightDec
                });
                scrollStateModel.trigger('change:scrollHeight', scrollStateModel, scrollContainer[0].scrollHeight);
                scrollStateModel.trigger('change:clientHeight', scrollStateModel, scrollContainer[0].clientHeight);
            }, this);
            scrollStateModel.on('change:scrollVisible', function (model, val) {
                scrollContainer.css({
                    width: 'calc(100% + ' + (val ? scrollBarWidth : 0) + 'px)'
                });
                otherScroll.css({
                    display: val ? 'block' : 'none'
                });
                this.fixHeaderCellWidth();
            }, this);
            scrollStateModel.on('change:clientHeight', function (model, val) {
                otherScroll.css({
                    height: val - heightDec
                });
            }, this);
            scrollStateModel.on('change:clientWidth', function (model, val) {
                otherScroll.css({
                    marginLeft: val - scrollBarWidth
                });
            }, this);
            scrollStateModel.on('change:scrollHeight', function (model, val) {
                otherScrollInner.css({
                    height: val - heightDec
                });
            });
            scrollStateModel.on('change:scrollTop', function (model, val) {
                otherScroll.scrollTop(val);
            }, this);
            function setup() {
                scrollStateModel.set({
                    headerHeight: self.headerHeight
                });
                self.scrollVisible = scrollContainer[0].clientHeight + 1 /*IE fix*/ < scrollContainer[0].scrollHeight;
                scrollStateModel.set({
                    scrollVisible: self.scrollVisible,
                    scrollHeight:  scrollContainer[0].scrollHeight,
                    clientHeight:  scrollContainer[0].clientHeight,
                    clientWidth:   scrollContainer[0].clientWidth,
                    scrollTop:     scrollContainer[0].scrollTop
                });
            }
            scrollContainer.on('scroll', setup);
            otherScroll.on('scroll', function () {
                var mainScrollTop = scrollContainer.scrollTop(),
                    otherScrollTop = otherScroll.scrollTop();
                if (mainScrollTop !== otherScrollTop) {
                    scrollContainer.scrollTop(otherScroll.scrollTop());
                    if (self.currentFloatTheadMode === 'relative') {
                        self.reposition();
                    }
                }
            });
            setup();
            return setup;
        },

        fixHeightInFloatTheadMode: function () {
            var currentClientRect = this.cachedEls.otherScrollContainer[0].getBoundingClientRect();
            if (!this.lastClientRect || (this.lastClientRect.top !== currentClientRect.top ||
                this.lastClientRect.left !== currentClientRect.left ||
                this.lastClientRect.right !== currentClientRect.right)) {
                if (this.layout === 'fullscreen') {
                    // adjust max height
                    this.$grid.parents('.grid-scrollable-container').css({
                        maxHeight: this.grid.getCssHeightCalcExpression()
                    });
                }
                if (!this.lastClientRect || (this.lastClientRect.left !== currentClientRect.left ||
                    this.lastClientRect.right !== currentClientRect.right)) {
                    this.fixHeaderCellWidth();
                } else {
                    this.reposition();
                }
            }
            this.lastClientRect = currentClientRect;
        },

        /**
         *
         * @param el
         * @returns {{top: number, left: Number, bottom: Number, right: Number}}
         */
        getVisibleRect: function (el) {
            var current = el,
                tableRect = current.getBoundingClientRect(),
                midRect = tableRect,
                borders,
                resultRect = {
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
            while (current !== document.documentElement) {
                midRect = current.getBoundingClientRect();
                borders = $.fn.getBorders(current);

                // console.log(current, current.id, midRect);

                if (tools.isMobile()) {
                    /**
                     * Equals header height. Cannot calculate dynamically due to issues on ipad
                     */
                    if (resultRect.top < 54 && current.id === 'top-page' && !this.cachedEls.body.hasClass('input-focused')) {
                        resultRect.top = 54;
                    } else if (resultRect.top < 44 && current.className === 'widget-content') {
                        resultRect.top = 44;
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
    };

    return FloatingHeader;
});
