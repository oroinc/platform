define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BasePlugin = require('oroui/js/app/plugins/base/plugin');
    const Backbone = require('backbone');
    const mediator = require('oroui/js/mediator');
    const scrollHelper = require('oroui/js/tools/scroll-helper');
    const tools = require('oroui/js/tools');

    const FloatingHeaderPlugin = BasePlugin.extend({
        initialize: function(grid) {
            this.grid = grid;
            this.grid.on('shown', this.onGridShown.bind(this));
            this.grid.on('changeAppearance', () => {
                // remove header height cache
                delete this.headerHeight;
            });

            this.selectMode = this.selectMode.bind(this);
            this.checkLayout = this.checkLayout.bind(this);
            this.fixHeaderCellWidth = this.fixHeaderCellWidth.bind(this);
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

            this.$el.addClass('with-floating-header');

            this.rescrollCb = this.enableOtherScroll();
            this.fixHeaderCellWidth();
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
            }

            this.$el.removeClass('with-floating-header');

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
                otherScrollContainer: this.$grid.parents('.other-scroll-container:first'),
                gridScrollableContainer: this.$grid.parents('.grid-scrollable-container:first'),
                otherScroll: this.$el.find('.other-scroll'),
                otherScrollInner: this.$el.find('.other-scroll > div'),
                thead: this.$grid.find('thead:first'),
                theadTr: this.$grid.find('thead:first tr:first')
            };
            if (!this.headerHeight) {
                this.headerHeight = this.domCache.theadTr.height();
            }
        },

        supportDropdowns: function() {
            const debouncedHideDropdowns = _.debounce(() => {
                this.domCache.thead.find('.show > [data-toggle="dropdown"]').trigger('tohide.bs.dropdown');
            }, 100, true);
            // use capture phase to scroll dropdown toggle into view before dropdown will be opened
            this.$grid[0].addEventListener('click', e => {
                const targetIsCheckbox = $(e.target).is('input[type=checkbox]');
                const dropdownToggle = $(e.target).closest('[data-toggle="dropdown"]');
                if (
                    dropdownToggle.length &&
                    dropdownToggle.parent().is('thead:first .dropdown:not(.show)') &&
                    !targetIsCheckbox
                ) {
                    // this will hide dropdowns and ignore next calls to it
                    debouncedHideDropdowns();
                    this.isHeaderDropdownVisible = true;
                    scrollHelper.scrollIntoView(dropdownToggle[0], void 0, 10, 10);
                }
            }, true);
            this.$grid.on('hide.bs.dropdown', () => {
                this.isHeaderDropdownVisible = false;
                this.selectMode();
            });
            this.domCache.gridContainer.parents().add(document).on('scroll.float-thead', () => {
                debouncedHideDropdowns();
                this.checkLayout();
            });

            this.domCache.gridScrollableContainer.on('updateScroll', this.selectMode.bind(this));
        },

        fixHeaderCellWidth: function() {
            mediator.trigger('gridHeaderCellWidth:beforeUpdate');

            this.setupCache();
            const headerCells = this.domCache.headerCells;
            const scrollLeft = this.domCache.gridScrollableContainer[0].scrollLeft;
            let widthDecrement = 0;
            const widths = [];
            // remove style
            headerCells.attr('style', '');
            this.$grid.css({width: ''});
            this.domCache.gridContainer.css({width: ''});
            this.$el.removeClass('floatThead');

            const totalWidth = this.$grid[0].scrollWidth;

            // save widths
            headerCells.each(function(i, headerCell) {
                widths.push(headerCell.scrollWidth);
            });

            // FF sometimes gives wrong values, need to check
            const sumWidth = _.reduce(widths, function(a, b) {
                return a + b;
            });

            if (sumWidth !== totalWidth) {
                widthDecrement = (totalWidth - sumWidth) / widths.length;
            }

            // set exact sizes to header cells and cells in first row
            headerCells.each(function(i, headerCell) {
                const cellWidth = widths[i] + widthDecrement;
                headerCell.style.width = cellWidth + 'px';
                headerCell.style.maxWidth = cellWidth + 'px';
                headerCell.style.minWidth = cellWidth + 'px';
                headerCell.style.boxSizing = 'border-box';
            });

            if (this.currentFloatTheadMode !== 'default') {
                this.$el.addClass('floatThead');
            }
            this.$grid.css({
                width: totalWidth
            });
            this.domCache.gridContainer.css({
                width: totalWidth
            });
            this.domCache.gridScrollableContainer[0].scrollLeft = scrollLeft;

            mediator.trigger('gridHeaderCellWidth:updated');

            this.selectMode();
        },

        /**
         * Selects floating header mode
         */
        selectMode: function() {
            // get gridRect
            const tableRect = this.domCache.gridContainer[0].getBoundingClientRect();
            const visibleRect = scrollHelper.getVisibleRect(this.$grid[0], {
                top: -this.headerHeight
            }, this.currentFloatTheadMode === 'default');
            let mode = 'default';
            if (
                (Math.ceil(visibleRect.top) !== Math.ceil(tableRect.top) && tableRect.bottom > this.headerHeight) ||
                this.grid.layout === 'fullscreen'
            ) {
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
            let theadRect;
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
                        this.$el.addClass('floatThead-relative floatThead');
                        this._ensureTHeadSizing();
                    }
                    theadRect = this.domCache.thead[0].getBoundingClientRect();
                    requestAnimationFrame(() => {
                        this.domCache.thead.css({
                            width: '',
                            top: visibleRect.top - tableRect.top,
                            left: ''
                        });
                        this.domCache.theadTr.css({
                            [`margin${_.isRTL() ? 'Right' : 'Left'}`]: _.isRTL()
                                ? theadRect.right - tableRect.right
                                : tableRect.left - theadRect.left
                        });
                    });
                    if (mode === 'relative') {
                        this._lastScrollTop = this.domCache.gridScrollableContainer.scrollTop();
                    }
                    break;
                case 'fixed':
                    // provides good scroll experience
                    if (this.currentFloatTheadMode !== mode) {
                        this.$el.removeClass('floatThead-relative');
                        this.$el.addClass('floatThead-fixed floatThead');
                        this._ensureTHeadSizing();
                    }
                    requestAnimationFrame(() => {
                        let top = visibleRect.top;

                        // On the iOS device need to fix value from getBoundingClientRect()
                        if (tools.isIOS() && tools.isDesktop()) {
                            top += document.scrollingElement.scrollTop;
                        }

                        this.domCache.thead.css({
                            // show only visible part
                            top: top,
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
                            [`margin${_.isRTL() ? 'Right' : 'Left'}`]: _.isRTL()
                                ? theadRect.right - tableRect.right
                                : tableRect.left - theadRect.left
                        });
                    });
                    break;
                default:
                    if (this.currentFloatTheadMode !== mode) {
                        requestAnimationFrame(() => {
                            this.$grid.find('.thead-sizing').remove();
                            this.$el.removeClass('floatThead-relative floatThead-fixed floatThead');
                            // remove extra styles
                            this.domCache.thead.attr('style', '');
                            this.domCache.theadTr.attr('style', '');
                            // cleanup
                        });
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
            const $theadSizingElement = this.$grid.find('.thead-sizing');
            if ($theadSizingElement.length) {
                $theadSizingElement.remove();
                this._ensureTHeadSizing();
            }
            this.fixHeaderCellWidth();
        },

        /**
         * Creates thead clone if it does not exist
         *
         * @protected
         */
        _ensureTHeadSizing: function() {
            if (!this.$grid.find('.thead-sizing').length) {
                const sizingThead = this.domCache.thead.clone();
                sizingThead.addClass('thead-sizing');
                sizingThead.find('th').attr('style', '');
                sizingThead.insertAfter(this.domCache.thead);
            }
        },

        /**
         * Enables other scroll functionality
         */
        enableOtherScroll: function() {
            let heightDec;
            const self = this;
            const scrollContainer = this.domCache.gridScrollableContainer;
            const otherScroll = this.domCache.otherScroll;
            const otherScrollInner = this.domCache.otherScrollInner;
            const scrollBarWidth = mediator.execute('layout:scrollbarWidth');
            const scrollStateModel = new Backbone.Model();

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
            }, this);
            scrollStateModel.on('change:clientHeight', function(model, val) {
                otherScroll.css({
                    height: val - heightDec
                });
            }, this);
            scrollStateModel.on('change:clientWidth', function(model, val) {
                otherScroll.css({
                    [`margin${_.isRTL() ? 'Right' : 'Left'}`]: val - scrollBarWidth
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

                const clientRectHeight = scrollContainer[0].getBoundingClientRect().height;
                let scrollHeight = scrollContainer[0].scrollHeight;
                const offsetHeight = scrollContainer[0].offsetHeight;

                if (offsetHeight !== clientRectHeight && offsetHeight === Math.round(clientRectHeight)) {
                    // ie/edge bounding rect height may include fraction
                    scrollHeight -= 1;
                }

                self.scrollVisible = scrollContainer[0].clientHeight < scrollHeight;

                scrollStateModel.set({
                    visible: self.scrollVisible,
                    scrollHeight: scrollContainer[0].scrollHeight,
                    clientHeight: scrollContainer[0].clientHeight,
                    scrollTop: scrollContainer[0].scrollTop
                });
                // update width in separate cycle
                // it can change during visibility change
                scrollStateModel.set({
                    clientWidth: scrollContainer[0].clientWidth
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
            this.domCache.gridScrollableContainer.off('updateScroll');
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
            if (!this.connected) {
                return;
            }
            let scrollLeft;
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
            const scrollContainerRect = this.domCache.otherScrollContainer[0].getBoundingClientRect();
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
                this.fixHeaderCellWidth();
                scrollHelper.scrollIntoView(cell.el, (el, rect) => {
                    if (this.domCache.gridScrollableContainer &&
                        this.domCache.gridScrollableContainer.length &&
                        el === this.domCache.gridScrollableContainer[0]) {
                        rect.top += this.headerHeight;
                    }
                });
                e.preventDefault();
            }
        }
    });

    return FloatingHeaderPlugin;
});
