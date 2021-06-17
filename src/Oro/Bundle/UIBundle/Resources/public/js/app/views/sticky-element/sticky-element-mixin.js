define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const scrollHelper = require('oroui/js/tools/scroll-helper');

    const stickyElementMixin = {
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
            const $el = this.$stickyElement = options.$stickyElement;
            const stickyOptions = this.stickyOptions = _.defaults(options.stickyOptions || {}, this.stickyOptions);

            if ($el && stickyOptions.enabled) {
                this._setStickyPosition = requestAnimationFrame.bind(window, this._setStickyPosition.bind(this));

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
            const $el = this.$stickyElement;
            const el = $el.get(0);
            const options = this.stickyOptions;
            const elStyle = $el.css(['position', 'display', 'verticalAlign', 'float']);
            const offsets = this._getOffsets($el);

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

            const elemRect = element.getBoundingClientRect();
            const bodyRect = document.body.getBoundingClientRect();

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
            const options = this.stickyOptions;
            const $el = this.$stickyElement;
            const scrollY = scrollHelper.getScrollY();
            const $relElem = (!options.relativeTo || options.relativeTo === 'parent')
                ? $el.parent()
                : $(options.relativeTo);
            const relElemCoords = this._getOffsets($relElem);

            if (scrollY >= relElemCoords.top) {
                const $stickyStub = this.$stickyStub || this._stick();

                const isStubFixed = ($stickyStub.css('position') === 'fixed');
                const stubCoords = this._getOffsets($stickyStub);

                const relDeltaTop = !isStubFixed && options.keepYOffset
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
            const options = this.stickyOptions;

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
