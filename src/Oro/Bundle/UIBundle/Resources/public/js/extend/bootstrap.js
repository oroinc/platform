define([
    'jquery',
    'underscore',
    'oroui/js/tools/scroll-helper',
    'bootstrap',
    './bootstrap/bootstrap-dropdown'
], function($, _, scrollHelper) {
    'use strict';

    /**
     * fix endless loop
     * Based on https://github.com/Khan/bootstrap/commit/378ab557e24b861579d2ec4ce6f04b9ea995ab74
     * Updated to support two modals on page
     */
    $.fn.modal.Constructor.prototype.enforceFocus = function() {
        var that = this;
        $(document)
            .off('focusin.modal') // guard against infinite focus loop
            .on('focusin.modal', function safeSetFocus(e) {
                if (that.$element[0] !== e.target && !that.$element.has(e.target).length) {
                    $(document).off('focusin.modal');
                    that.$element.focus();
                    $(document).on('focusin.modal', safeSetFocus);
                }
            });
    };

    /**
     * This customization allows to define own click, render, show functions for Typeahead
     */
    var Typeahead;
    var origTypeahead = $.fn.typeahead.Constructor;
    var origFnTypeahead = $.fn.typeahead;

    Typeahead = function(element, options) {
        var opts = $.extend({}, $.fn.typeahead.defaults, options);
        this.click = opts.click || this.click;
        this.render = opts.render || this.render;
        this.show = opts.show || this.show;
        origTypeahead.apply(this, arguments);
    };

    Typeahead.prototype = origTypeahead.prototype;
    Typeahead.prototype.constructor = Typeahead;

    $.fn.typeahead = function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('typeahead');
            var options = typeof option === 'object' && option;
            if (!data) {
                $this.data('typeahead', (data = new Typeahead(this, options)));
            }
            if (typeof option === 'string') {
                data[option]();
            }
        });
    };

    $.fn.typeahead.defaults = origFnTypeahead.defaults;
    $.fn.typeahead.Constructor = Typeahead;
    $.fn.typeahead.noConflict = origFnTypeahead.noConflict;

    /**
     * Customization for Tooltip/Popover
     *  - propagate hide action to delegated tooltips/popovers
     *  - propagate destroy action to delegated tooltips/popovers
     */
    var Tooltip = $.fn.tooltip.Constructor;
    var Popover = $.fn.popover.Constructor;

    var delegateAction = function(method, action) {
        return function() {
            var type = this.type;
            // Tooltip/Popover delegates initialization to element -- propagate action them first
            if (this.options.selector) {
                this.$element.find(this.options.selector).each(function() {
                    if ($(this).data(type)) {
                        $(this).popover(action);
                    }
                });
            }
            // clear timeout if it exists
            if (this.timeout) {
                clearTimeout(this.timeout);
                delete this.timeout;
            }
            return method.apply(this, arguments);
        };
    };

    Popover.prototype.hide = delegateAction(Popover.prototype.hide, 'hide');
    Popover.prototype.destroy = delegateAction(Popover.prototype.destroy, 'destroy');
    Tooltip.prototype.hide = delegateAction(Tooltip.prototype.hide, 'hide');
    Tooltip.prototype.destroy = delegateAction(Tooltip.prototype.destroy, 'destroy');
    Popover.prototype.arrow = function() {
        this.$arrow = this.$arrow || this.tip().find('.arrow');
        return this.$arrow;
    };

    Popover.prototype.tip = function() {
        if (!this.$tip) {
            this.$tip = $(this.options.template);

            var addClass = this.options.class || '';
            if (addClass && !this.$tip.hasClass(addClass)) {
                this.$tip.addClass(addClass);
            }

            var addCloseButton = this.options.closeButton || false;
            if (!addCloseButton) {
                this.$tip.find('.popover-close').hide();
            }

            this.$tip.find('.popover-close').on('click', _.bind(function () {
                this.hide();
            }, this));
        }
        return this.$tip;
    };

    $.fn.popover.defaults = $.extend({} , $.fn.popover.defaults, {
        template: [
            '<div class="popover">',
                '<div class="arrow"></div>',
                '<h3 class="popover-title"></h3>',
                '<button class="popover-close"><i class="icon-remove"></i></button>',
                '<div class="popover-content"></div>',
            '</div>'
        ].join('')
    });

    Popover.prototype.setContent = _.wrap(Popover.prototype.setContent, function(oroginal) {
        oroginal.apply(this, _.rest(arguments));
        if (this.getTitle().length > 0) {
            this.$tip.find('.popover-title').show();
        } else {
            this.$tip.find('.popover-title').hide();
        }
    });

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
    var originalShow = Popover.prototype.show;
    Popover.prototype.show = function() {
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
                    if (typeof _this.options.hideOnScroll !== 'undefined' && !_this.options.hideOnScroll) {
                        return;
                    }
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

        originalShow.apply(this, arguments);
    };

    var originalHide = Popover.prototype.hide;
    Popover.prototype.hide = function() {
        clearInterval(this.trackPositionInterval);
        originalHide.apply(this, arguments);
    };
});
