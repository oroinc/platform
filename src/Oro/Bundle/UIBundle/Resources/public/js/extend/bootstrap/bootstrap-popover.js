define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var scrollHelper = require('oroui/js/tools/scroll-helper');
    require('bootstrap');

    var Tooltip = $.fn.tooltip.Constructor;
    var Popover = $.fn.popover.Constructor;

    _.extend(Popover.prototype, _.pick(Tooltip.prototype,
        ['hide', 'destroy']));

    Popover.prototype.arrow = function() {
        this.$arrow = this.$arrow || this.tip().find('.arrow');
        return this.$arrow;
    };

    Popover.prototype.tip = function() {
        if (!this.$tip) {
            this.$tip = $(this.options.template);
            var addClass = this.$element.data('class');
            if (addClass && !this.$tip.hasClass(addClass)) {
                this.$tip.addClass(addClass);
            }
        }
        return this.$tip;
    };

    Popover.prototype.applyPlacement = function(offset, placement) {
        /** Following snippet was copied from original Bootstrap method to fix bug with offset correction.
         *  See comment in the snippet
         */
        /* jshint ignore:start */
        // jscs:disable
        var $tip = this.tip()
            , width = $tip[0].offsetWidth
            , height = $tip[0].offsetHeight
            , actualWidth
            , actualHeight
            , delta
            , replace

        $tip
            .offset(offset)
            .addClass(placement)
            .addClass('in')

        actualWidth = $tip[0].offsetWidth
        actualHeight = $tip[0].offsetHeight

        if (placement == 'top' && actualHeight != height) {
            offset.top = offset.top + height - actualHeight
            replace = true
        }

        if (placement == 'bottom' || placement == 'top') {
            delta = 0

            if (offset.left < 0){
                delta = offset.left * -2
                offset.left = 0
                // temporarily remove placement class to avoid affecting of margins to offset method
                $tip.removeClass(placement).offset(offset).addClass(placement);
                actualWidth = $tip[0].offsetWidth
                actualHeight = $tip[0].offsetHeight
            }

            this.replaceArrow(delta - width + actualWidth, actualWidth, 'left')
        } else {
            this.replaceArrow(actualHeight - height, actualHeight, 'top')
        }

        if (replace) $tip.offset(offset)
        // jscs:enable
        /* jshint ignore:end */

        if (!this.$element.data('noscroll')) {
            /*
             * SCROLL support
             */
            var adjustmentLeft = scrollHelper.scrollIntoView(this.$tip[0]);

            /*
             * SHIFT support
             */
            var visibleRect = scrollHelper.getVisibleRect(this.$tip[0]);

            if (placement === 'right' || placement === 'left') {
                var outerHeight = this.$tip.outerHeight();
                var visibleHeight = visibleRect.bottom - visibleRect.top;
                if (visibleHeight < outerHeight - /* fixes floating pixel calculation */ 1) {
                    // still doesn't match, decrease height and move into visible area
                    this.$tip.css({
                        height: this.$tip.outerHeight()
                    });
                    //find adjustment to move tooltip
                    var adjustment = outerHeight - visibleHeight;
                    //change adjustemnt direction if needed
                    if (adjustmentLeft.vertical < 0) {
                        adjustment = -adjustment;
                    }

                    this.$tip.css({
                        top: parseFloat(this.$tip.css('top')) + adjustment
                    });
                    if (!scrollHelper.isCompletelyVisible(this.$tip[0]) && adjustment < 0) {
                        /**
                         * make a second attempt to move tooltip up
                         * to fix the issue when unnecessary scroll is done by scrollIntoView
                         */
                        this.$tip.css({
                            top: parseFloat(this.$tip.css('top')) + adjustment
                        });
                        adjustment += adjustment;
                    }
                    //check visible area after move, update arrow position and height
                    var newVisibleRect = scrollHelper.getVisibleRect(this.$tip[0]);
                    var newVisibleHeight = newVisibleRect.bottom - newVisibleRect.top;
                    this.$tip.css({
                        maxHeight: newVisibleHeight
                    });
                    var centerChange = (outerHeight - newVisibleHeight) / 2;
                    this.$arrow.css({
                        top: 'calc(50% + ' + (centerChange - adjustment) + 'px)'
                    });
                }
            }
        }
    };

    Popover.prototype.show = _.wrap(Popover.prototype.show, function(func) {
        if (this.$element.attr('data-container') && this.$element.attr('data-container').length > 0) {
            this.options.container = this.$element.closest(this.$element.attr('data-container'));
            if (!this.options.container.length) {
                this.options.container = false;
            }
        }

        if (this.options.container && this.$element.length) {
            // if container option is specified - popover will be closed when position of $element is changed
            var _this = this;
            var el = this.$element[0];
            var initialPos = el.getBoundingClientRect();
            this.trackPositionInterval = setInterval(function() {
                var currentPos = el.getBoundingClientRect();
                if (currentPos.left !== initialPos.left || currentPos.top !== initialPos.top) {
                    _this.hide();
                }
            }, 300);
        }

        // remove adjustments made by applyPlacement
        if (this.$tip) {
            this.$tip.css({
                height: '',
                maxHeight: '',
                top: ''
            });
        }
        if (this.$arrow) {
            this.$arrow.css({
                top: ''
            });
        }

        return func.apply(this, _.rest(arguments));
    });

    Popover.prototype.hide = _.wrap(Popover.prototype.hide, function(func) {
        clearInterval(this.trackPositionInterval);
        return func.apply(this, _.rest(arguments));
    });
});
