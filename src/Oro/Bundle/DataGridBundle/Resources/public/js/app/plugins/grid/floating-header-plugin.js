define(function(require) {
    'use strict';

    var FloatingHeaderPlugin;
    var $ = require('jquery');
    var _ = require('underscore');
    var BasePlugin = require('oroui/js/app/plugins/base/plugin');
    var Backbone = require('backbone');
    var mediator = require('oroui/js/mediator');
    var scrollHelper = require('oroui/js/tools/scroll-helper');
    var scrollBarWidth = mediator.execute('layout:scrollbarWidth');

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
            this.headerHeight = this.domCache.theadTr.height();

            this.isHeaderCellWidthFixed = false;
            this.rescrollCb = this.enableOtherScroll();
            if (!this.isHeaderCellWidthFixed) {
                this.fixHeaderCellWidth();
            }
            this.supportDropdowns();

            this.listenTo(mediator, 'layout:headerStateChange', this.checkLayout);
            this.listenTo(mediator, 'layout:reposition', this.checkLayout);
            this.listenTo(this.grid, 'content:update', this.onGridContentUpdate);
            this.listenTo(this.grid, 'ensureCellIsVisible', this.ensureCellIsVisible);
            this.checkLayoutIntervalId = setInterval(this.checkLayout, 400);
            this.connected = true;
            FloatingHeaderPlugin.__super__.enable.call(this);
        },

        disable: function() {
            this.connected = false;
            clearInterval(this.checkLayoutIntervalId);

            this.domCache.gridContainer.parents().add(document).off('.float-thead');

            if (!this.manager.disposing) {
                this.setFloatTheadMode('default');
                this.disableOtherScroll();
                this.$grid.off('.float-thead');
                // remove css
                this.domCache.headerCells.attr('style', '');
                this.domCache.firstRowCells.attr('style', '');
            }
            FloatingHeaderPlugin.__super__.disable.call(this);
        },

        setupCache: function() {
            this.$grid = this.grid.$grid;
            this.$el = this.grid.$el;
            this.documentHeight = scrollHelper.documentHeight();
            this.domCache = {
                body: $(document.body),
                gridContainer: this.$grid.parent(),
                headerCells: this.$grid.find('th:first').parent().find('th.renderable'),
                firstRowCells: this.$grid.find('tbody tr:not(.thead-sizing-row):first').children('td'),
                otherScrollContainer: this.$grid.parents('.other-scroll-container:first'),
                gridScrollableContainer: this.$grid.parents('.grid-scrollable-container:first'),
                otherScroll: this.$el.find('.other-scroll'),
                otherScrollInner: this.$el.find('.other-scroll > div'),
                thead: this.$grid.find('thead:first'),
                theadTr: this.$grid.find('thead:first tr:first')
            };
        },

        supportDropdowns: function () {
            // use capture phase to scroll dropdown toggle into view before dropdown will be opened
            this.$grid[0].addEventListener('click', _.bind(function (e) {
                var dropdownToggle = $(e.target).closest('.dropdown-toggle');
                if (dropdownToggle.length && dropdownToggle.parent().is('thead:first .dropdown:not(.open)')) {
                    // this will hide dropdowns and ignore next calls to it
                    debouncedHideDropdowns();
                    this.isHeaderDropdownVisible = true;
                    scrollHelper.scrollIntoView(dropdownToggle[0], void 0, 10, 10);
                }
            }, this), true);
            this.$grid.on('hide.bs.dropdown', '.dropdown.open', _.bind(function () {
                this.isHeaderDropdownVisible = false;
                this.selectMode();
            }, this));
            var debouncedHideDropdowns = _.debounce(_.bind(function () {
                this.domCache.thead.find('.dropdown.open .dropdown-toggle').trigger('tohide.bs.dropdown');
            }, this), 100, true);
            this.domCache.gridContainer.parents().add(document).on('scroll.float-thead', _.bind(function () {
                debouncedHideDropdowns();
                this.checkLayout();
            }, this));
        },

        fixHeaderCellWidth: function() {
            this.isHeaderCellWidthFixed = true;
            this.setupCache();
            var headerCells = this.domCache.headerCells;
            var firstRowCells = this.domCache.firstRowCells;
            var totalWidth;
            var sumWidth;
            var widthDecrement = 0;
            var widths = [];
            var self = this;
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
            var visibleRect = scrollHelper.getVisibleRect(this.domCache.gridContainer[0], {
                top: -this.headerHeight
            }, this.currentFloatTheadMode === 'default');
            var mode = 'default';
            if (visibleRect.top !== tableRect.top || this.grid.layout === 'fullscreen') {
                mode = this.isHeaderDropdownVisible ? 'relative' : 'fixed';
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
                visibleRect = scrollHelper.getVisibleRect(this.domCache.gridContainer[0], {
                    top: -this.headerHeight
                }, this.currentFloatTheadMode === 'default');
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
        onGridContentUpdate: function() {
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
                    scrollTop:     scrollContainer[0].scrollTop
                });
                // update width in separate cycle
                // it can change during visibility change
                scrollStateModel.set({
                    clientWidth:   scrollContainer[0].clientWidth
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
                } else {
                    if (this._lastClientRect.bottom !== scrollContainerRect.bottom) {
                        this.rescrollCb();
                    }
                }
            }
            this._lastClientRect = scrollContainerRect;
        },

        ensureCellIsVisible: function(e, cell) {
            if (e.isDefaultPrevented()) {
                return;
            }
            if (this.currentFloatTheadMode in {relative: true, fixed: true}) {
                var _this = this;
                this.fixHeaderCellWidth();
                scrollHelper.scrollIntoView(cell.el, function(el, rect) {
                    if (_this.domCache.gridScrollableContainer &&
                        _this.domCache.gridScrollableContainer.length &&
                        el === _this.domCache.gridScrollableContainer[0]) {
                        rect.top += _this.headerHeight;
                    }
                });
                e.preventDefault();
            }
        }
    });

    return FloatingHeaderPlugin;
});
