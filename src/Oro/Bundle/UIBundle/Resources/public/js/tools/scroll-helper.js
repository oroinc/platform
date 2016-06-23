define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    require('jquery-ui');

    var scrollHelper = {
        /**
         * Height of header on mobile devices
         */
        MOBILE_HEADER_HEIGHT: 54,

        /**
         * Height of header on mobile devices
         */
        MOBILE_POPUP_HEADER_HEIGHT: 44,

        /**
         * Cached scrollbarWidth value
         */
        _scrollbarWidth: -1,

        /**
         * Try to calculate the scrollbar width for your browser/os
         * @return {Number}
         */
        scrollbarWidth: function() {
            if (this._scrollbarWidth === -1) {
                this._scrollbarWidth = $.position.scrollbarWidth();
            }
            return this._scrollbarWidth;
        },

        /**
         * Cached documentHeight value
         */
        _documentHeight: -1,

        /**
         * Returns actual documentHeight
         * @return {Number}
         */
        documentHeight: function() {
            if (this._documentHeight === -1) {
                this._documentHeight = $(document).height();
            }
            return this._documentHeight;
        },

        /**
         * Returns visible rect of DOM element
         *
         * @param el
         * @param {{top: number, left: number, bottom: number, right: number}} increments for each initial rect side
         * @param {boolean} forceInvisible if true - function will return initial rect when element is out of screen
         * @param {Function} onAfterGetClientRect - callback called after each getBoundingClientRect
         * @returns {{top: number, left: number, bottom: number, right: number}}
         */
        getVisibleRect: function(el, increments, forceInvisible, onAfterGetClientRect) {
            increments = increments || {};
            _.defaults(increments, {
                top: 0,
                left: 0,
                bottom: 0,
                right: 0
            });
            var current = el;
            var midRect = this.getEditableClientRect(current);
            if (onAfterGetClientRect) {
                onAfterGetClientRect(current, midRect);
            }
            var borders;
            var resultRect = {
                top: midRect.top + increments.top,
                left: midRect.left + increments.left,
                bottom: midRect.bottom + increments.bottom,
                right: midRect.right + increments.right
            };
            if (
                (resultRect.top === 0 && resultRect.bottom === 0) || // no-data block is shown
                    (resultRect.top > this.documentHeight() && forceInvisible)
                ) {
                // no need to calculate anything
                return resultRect;
            }
            current = current.parentNode;
            while (current && current.getBoundingClientRect) {
                /**
                 * Equals header height. Cannot calculate dynamically due to issues on ipad
                 */
                if (resultRect.top < this.MOBILE_HEADER_HEIGHT && tools.isMobile()) {
                    if (current.id === 'top-page' && !$(document.body).hasClass('input-focused')) {
                        resultRect.top = this.MOBILE_HEADER_HEIGHT;
                    } else if (current.className.split(/\s+/).indexOf('widget-content') !== -1) {
                        resultRect.top = this.MOBILE_POPUP_HEADER_HEIGHT;
                    }
                }

                midRect = this.getFinalVisibleRect(current, onAfterGetClientRect);
                borders = $.fn.getBorders(current);

                var style = window.getComputedStyle(current);
                if (style.overflowX !== 'visible' || style.overflowY  !== 'visible') {
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
                }
                current = current.parentNode;
            }

            if (resultRect.top < 0) {
                resultRect.top = 0;
            }

            return resultRect;
        },

        getFinalVisibleRect: function(current, onAfterGetClientRect) {
            var rect = this.getEditableClientRect(current);
            if (onAfterGetClientRect) {
                onAfterGetClientRect(current, rect);
            }

            var border = $.fn.getBorders(current);
            var verticalScrollIsVisible = (current.offsetWidth - border.left - border.right) > current.clientWidth;
            var horizontalScrollIsVisible = (current.offsetHeight - border.top - border.bottom) > current.clientHeight;

            if (horizontalScrollIsVisible && current.scrollHeight > current.clientHeight) {
                rect.bottom -= this.scrollbarWidth();
            }
            if (verticalScrollIsVisible && current.scrollWidth > current.clientWidth) {
                rect.right -= this.scrollbarWidth();
            }
            return rect;
        },

        getEditableClientRect: function(el) {
            var rect = el.getBoundingClientRect();
            return {
                top: rect.top,
                left: rect.left,
                bottom: rect.bottom,
                right: rect.right
            };
        },

        isCompletelyVisible: function(el, onAfterGetClientRect) {
            var rect = this.getEditableClientRect(el);
            if (onAfterGetClientRect) {
                onAfterGetClientRect(el, rect);
            }
            if (rect.top === rect.bottom || rect.left === rect.right) {
                return false;
            }
            var visibleRect = this.getVisibleRect(el, null, false, onAfterGetClientRect);
            return visibleRect.top === rect.top &&
                visibleRect.bottom === rect.bottom &&
                visibleRect.left === rect.left &&
                visibleRect.right === rect.right;
        },

        scrollIntoView: function(el, onAfterGetClientRect) {
            if (this.isCompletelyVisible(el, onAfterGetClientRect)) {
                return {vertical: 0, horizontal: 0};
            }

            var rect = this.getEditableClientRect(el);
            if (onAfterGetClientRect) {
                onAfterGetClientRect(el, rect);
            }
            if (rect.top === rect.bottom || rect.left === rect.right) {
                return {vertical: 0, horizontal: 0};
            }
            var visibleRect = this.getVisibleRect(el, null, false, onAfterGetClientRect);
            var scrolls = {
                vertical: rect.top !== visibleRect.top ? visibleRect.top - rect.top :
                    (rect.bottom !== visibleRect.bottom ? visibleRect.bottom - rect.bottom : 0),
                horizontal: rect.left !== visibleRect.left ? visibleRect.left - rect.left :
                    (rect.right !== visibleRect.right ? visibleRect.right - rect.right : 0)
            };

            return this.applyScrollToParents(el, scrolls);
        },

        applyScrollToParents: function(el, scrolls) {
            if (!scrolls.horizontal && !scrolls.vertical) {
                return scrolls;
            }

            // make a local copy to don't change initial object
            scrolls = _.extend({}, scrolls);

            $(el).parents().each(function() {
                var $this = $(this);
                if (scrolls.horizontal !== 0) {
                    switch ($this.css('overflowX')) {
                        case 'auto':
                        case 'scroll':
                            if (this.clientWidth < this.scrollWidth) {
                                var oldScrollLeft = this.scrollLeft;
                                this.scrollLeft = this.scrollLeft - scrolls.horizontal;
                                scrolls.horizontal += this.scrollLeft - oldScrollLeft;
                            }
                            break;
                        default:
                            break;
                    }
                }
                if (scrolls.vertical !== 0) {
                    switch ($this.css('overflowY')) {
                        case 'auto':
                        case 'scroll':
                            if (this.clientHeight < this.scrollHeight) {
                                var oldScrollTop = this.scrollTop;
                                this.scrollTop = this.scrollTop - scrolls.vertical;
                                scrolls.vertical += this.scrollTop - oldScrollTop;
                            }
                            break;
                        default:
                            break;
                    }
                }
            });

            return scrolls;
        }
    };

    // reset document height cache on resize
    $(window).bindFirst('resize', function() {
        scrollHelper._documentHeight = -1;
    });

    return scrollHelper;
});
