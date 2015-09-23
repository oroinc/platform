define(function(require) {
    'use strict';
    var $ = require('jquery');
    var _ = require('underscore');
    var layout = require('oroui/js/layout');
    var tools = require('oroui/js/tools');

    return {
        /**
         * Returns visible rect of DOM element
         *
         * @param el
         * @param {{top: number, left: Number, bottom: Number, right: Number}} increments for each initial rect side
         * @param {boolean} forceInvisible if true - function will return initial rect when element is out of screen
         * @returns {{top: number, left: Number, bottom: Number, right: Number}}
         */
        getVisibleRect: function(el, increments, forceInvisible) {
            increments = increments || {};
            _.defaults(increments, {
                top: 0,
                left: 0,
                bottom: 0,
                right: 0
            });
            var current = el;
            var midRect = current.getBoundingClientRect();
            var borders;
            var resultRect = {
                top: midRect.top + increments.top,
                left: midRect.left + increments.left,
                bottom: midRect.bottom + increments.bottom,
                right: midRect.right + increments.right
            };
            if (
                (resultRect.top === 0 && resultRect.bottom === 0) || // no-data block is shown
                    (resultRect.top > $(document).height() && forceInvisible) // grid is invisible
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
                        !$(document.body).hasClass('input-focused')) {
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
    };
});
