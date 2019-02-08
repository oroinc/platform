define(function(require) {
    'use strict';

    var stickyElementMixin;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var scrollHelper = require('oroui/js/tools/scroll-helper');

    stickyElementMixin = {
        $stickyElement: null,
        $stickyStub: null,

        stickyOptions: {
            enabled: false,
            stickyClass: 'sticky',
            stubClass: 'sticky-stub',
            pinnedClass: 'sticky-pinned',
            outOfViewportClass: 'sticky-out-of-viewport',
            relativeTo: 'parent',
            keepYOffset: true
        },

        /**
         * Initializes sticky behavior
         */
        initializeSticky: function(options) {
            var $el = this.$stickyElement = options.$stickyElement;
            var stickyOptions = this.stickyOptions = _.defaults(options.stickyOptions || {}, this.stickyOptions);

            if ($el && stickyOptions.enabled) {
                this._setStickyPosition = _.bind(requestAnimationFrame, window, _.bind(this._setStickyPosition, this));

                $el.addClass(stickyOptions.stickyClass);

                $(window).on('resize.sticky scroll.sticky', this._setStickyPosition);
                mediator.on('sticky.update', this._setStickyPosition);

                this._setStickyPosition();
            }
        },

        /**
         * Makes element stick to it's current position relative to container.
         *
         * @returns {jQuery} 'stub' element, with same dimensions, appended right after target element
         */
        _stick: function() {
            var $el = this.$stickyElement;
            var el = $el.get(0);
            var options = this.stickyOptions;
            var elStyle = $el.css(['position', 'display', 'verticalAlign', 'float']);
            var offsets = this._getOffsets($el);

            this.$stickyStub = $(document.createElement(el.tagName))
                .addClass(options.stubClass)
                .css(_.extend(
                    {},
                    elStyle,
                    _.pick(offsets, 'width', 'height')
                ))
                .insertAfter($el);

            $el
                .addClass(options.pinnedClass)
                .css(_.extend({
                    position: 'fixed'
                }, _.pick(offsets, 'top', 'left')));

            return this.$stickyStub;
        },

        /**
         * Unsticks the element, when it's on initial position or higher and removes the stub
         */
        _unstick: function() {
            if (this.$stickyElement) {
                this.$stickyElement
                    .removeClass(this.stickyOptions.pinnedClass)
                    .css({
                        top: '',
                        left: '',
                        position: ''
                    });
            }

            if (this.$stickyStub) {
                this.$stickyStub.remove();
                this.$stickyStub = null;
            }
        },

        /**
         * Gets coords object of element relative to body
         *
         * @param {element} element
         * @returns {object}
         */
        _getOffsets: function(element) {
            element = (element instanceof $) && element.last().get(0) || element;

            var elemRect = element.getBoundingClientRect();
            var bodyRect = document.body.getBoundingClientRect();

            return {
                left: Math.round(elemRect.left - bodyRect.left) || 0,
                top: Math.round(elemRect.top - bodyRect.top) || 0,
                width: elemRect.width || 0,
                height: elemRect.height || 0
            };
        },

        /**
         * Sets coords to button, toggles the stub
         */
        _setStickyPosition: function() {
            var options = this.stickyOptions;
            var $el = this.$stickyElement;
            var scrollY = scrollHelper.getScrollY();
            var $relElem = (!options.relativeTo || options.relativeTo === 'parent')
                ? $el.parent()
                : $(options.relativeTo);
            var relElemCoords = this._getOffsets($relElem);

            if (scrollY >= relElemCoords.top) {
                var $stickyStub = this.$stickyStub || this._stick();

                var isStubFixed = ($stickyStub.css('position') === 'fixed');
                var stubCoords = this._getOffsets($stickyStub);

                var relDeltaTop = !isStubFixed && options.keepYOffset
                    ? stubCoords.top - relElemCoords.top
                    : 0;

                $el
                    .toggleClass(options.outOfViewportClass, scrollY > stubCoords.top + stubCoords.height)
                    .css({
                        top: Math.max(-scrollY + relDeltaTop, relDeltaTop),
                        left: stubCoords.left
                    });
            } else {
                this._unstick();
            }
        },

        /**
         * Removes sticky element data and behavior
         */
        disposeSticky: function() {
            var options = this.stickyOptions;

            if (!this.stickyOptions.enabled) {
                return;
            }

            this._unstick();

            this.$stickyElement
                .removeClass(options.stickyClass)
                .removeClass(options.outOfViewportClass);

            $(window).off('resize.sticky scroll.sticky', this._setStickyPosition);
            mediator.off('sticky.update', this._setStickyPosition);
        }
    };

    return stickyElementMixin;
});
