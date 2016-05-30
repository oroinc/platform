define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var scrollspy = require('oroui/js/scrollspy');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var scrollHelper = require('oroui/js/tools/scroll-helper');

    require('bootstrap');
    require('jquery-ui');
    require('oroui/js/responsive-jquery-widget');

    var document = window.document;
    var console = window.console;
    var pageRenderedCbPool = [];

    var layout = {
        /**
         * Default padding to keep when calculate available height for fullscreen layout
         */
        PAGE_BOTTOM_PADDING: 10,

        /**
         * Height of header on mobile devices
         */
        MOBILE_HEADER_HEIGHT: scrollHelper.MOBILE_HEADER_HEIGHT,

        /**
         * Height of header on mobile devices
         */
        MOBILE_POPUP_HEADER_HEIGHT: scrollHelper.MOBILE_POPUP_HEADER_HEIGHT,

        /**
         * Minimal height for fullscreen layout
         */
        minimalHeightForFullScreenLayout: 300,

        /**
         * Keeps calculated devToolbarHeight. Please use getDevToolbarHeight() to retrieve it
         */
        devToolbarHeight: undefined,

        /**
         * List of elements with disabled scroll. Used to reset theirs state
         * @private
         */
        _scrollDisabledElements: null,

        /**
         * @returns {number} development toolbar height in dev mode, 0 in production mode
         */
        getDevToolbarHeight: function() {
            if (!mediator.execute('retrieveOption', 'debug')) {
                return 0;
            }
            if (!this.devToolbarHeightListenersAttached) {
                this.devToolbarHeightListenersAttached = true;
                $(window).on('resize', function() {
                    delete layout.devToolbarHeight;
                });
                mediator.on('debugToolbar:afterUpdateView', function() {
                    delete layout.devToolbarHeight;
                });
            }
            if (this.devToolbarHeight === void 0) {
                var devToolbarComposition = mediator.execute('composer:retrieve', 'debugToolbar', true);
                if (devToolbarComposition &&
                    devToolbarComposition.view &&
                    devToolbarComposition.view.$('.sf-toolbarreset').is(':visible')) {
                    this.devToolbarHeight = devToolbarComposition.view.$('.sf-toolbarreset').outerHeight();
                } else {
                    this.devToolbarHeight = 0;
                }
            }
            return this.devToolbarHeight;
        },

        /**
         * Initializes
         *  - form widgets
         *  - tooltips
         *  - popovers
         *  - scrollspy
         *
         * @param {string|HTMLElement|jQuery.Element} container
         */
        init: function(container) {
            var $container;

            $container = $(container);
            this.styleForm($container);

            scrollspy.init($container);

            $container.find('[data-toggle="tooltip"]').tooltip();

            this.initPopover($container);
        },

        initPopover: function(container) {
            var $items = container.find('[data-toggle="popover"]').filter(function() {
                // skip already initialized popovers
                return !$(this).data('popover');
            });
            $items.not('[data-close="false"]').each(function(i, el) {
                //append close link
                var content = $(el).data('content');
                content += '<i class="icon-remove popover-close"></i>';
                $(el).data('content', content);
            });

            $items.popover({
                animation: false,
                delay: {show: 0, hide: 0},
                html: true,
                container: false,
                trigger: 'manual'
            }).on('click.popover', function(e) {
                $(this).popover('toggle');
                e.preventDefault();
            });

            $('body')
                .on('click.popover-hide', function(e) {
                    var $target = $(e.target);
                    $items.each(function() {
                        //the 'is' for buttons that trigger popups
                        //the 'has' for icons within a button that triggers a popup
                        if (
                            !$(this).is($target) &&
                                $(this).has($target).length === 0 &&
                                ($('.popover').has($target).length === 0 || $target.hasClass('popover-close'))
                        ) {
                            $(this).popover('hide');
                        }
                    });
                }).on('click.popover-prevent', '.popover', function(e) {
                    if (e.target.tagName.toLowerCase() !== 'a') {
                        e.preventDefault();
                    }
                }).on('focus.popover-hide', 'select, input, textarea', function() {
                    $items.popover('hide');
                });
            mediator.once('page:request', function() {
                $('body').off('.popover-hide .popover-prevent');
            });
        },

        hideProgressBar: function() {
            var $bar = $('#progressbar');
            if ($bar.is(':visible')) {
                $bar.hide();
                $('#page').show();
            }
        },

        /**
         * Bind forms widget and plugins to elements
         *
         * @param {jQuery=} $container
         */
        styleForm: function($container) {
            $container.inputWidget('seekAndCreate');
            $container.one('content:changed', _.bind(this.styleForm, this, $container));
        },

        /**
         * Removes forms widget and plugins from elements
         *
         * @param {jQuery=} $container
         */
        unstyleForm: function($container) {
            $container.inputWidget('seekAndDestroy');
        },

        onPageRendered: function(cb) {
            if (document.pageReady) {
                _.defer(cb);
            } else {
                pageRenderedCbPool.push(cb);
            }
        },

        pageRendering: function() {
            document.pageReady = false;

            pageRenderedCbPool = [];
        },

        pageRendered: function() {
            document.pageReady = true;

            _.each(pageRenderedCbPool, function(cb) {
                try {
                    cb();
                } catch (ex) {
                    if (console && (typeof console.log === 'function')) {
                        console.log(ex);
                    }
                }
            });

            pageRenderedCbPool = [];
        },

        /**
         * Update modificators of responsive elements according to their containers size
         */
        updateResponsiveLayout: function() {
            _.defer(function() {
                $(document).responsive();
            });
        },

        /**
         * Returns available height for element if page will be transformed to fullscreen mode
         *
         * @param $mainEl
         * @param boundingClientRect - pass boundingClientRect of $mainEl to avoid it expensive calculation
         * @returns {number}
         */
        getAvailableHeight: function($mainEl, boundingClientRect) {
            var $parents = $mainEl.parents();
            var documentHeight = scrollHelper.documentHeight();
            var heightDiff = documentHeight -
                (boundingClientRect ? boundingClientRect : $mainEl[0].getBoundingClientRect()).top;
            $parents.each(function() {
                heightDiff += this.scrollTop;
            });
            return heightDiff - this.getDevToolbarHeight() - this.PAGE_BOTTOM_PADDING;
        },

        /**
         * Returns name of preferred layout for $mainEl
         *
         * @param $mainEl
         * @param boundingClientRect - pass boundingClientRect of $mainEl to avoid it expensive calculation
         * @returns {string}
         */
        getPreferredLayout: function($mainEl, boundingClientRect) {
            if (!this.hasHorizontalScroll() && !tools.isMobile() &&
                this.getAvailableHeight($mainEl, boundingClientRect) > this.minimalHeightForFullScreenLayout) {
                return 'fullscreen';
            } else {
                return 'scroll';
            }
        },

        /**
         * Disables ability to scroll of $mainEl's scrollable parents
         *
         * @param $mainEl
         * @returns {string}
         */
        disablePageScroll: function($mainEl) {
            if (this._scrollDisabledElements && this._scrollDisabledElements.length) {
                this.enablePageScroll();
            }
            var $scrollableParents = $mainEl.parents();
            $scrollableParents.scrollTop(0);
            $scrollableParents.addClass('disable-scroll');
            this._scrollDisabledElements = $scrollableParents;
        },

        /**
         * Enables ability to scroll where it was previously disabled
         *
         * @returns {string}
         */
        enablePageScroll: function() {
            if (this._scrollDisabledElements && this._scrollDisabledElements.length) {
                this._scrollDisabledElements.parents().removeClass('disable-scroll');
                delete this._scrollDisabledElements;
            }
        },

        /**
         * Returns true if page has horizontal scroll
         * @returns {boolean}
         */
        hasHorizontalScroll: function() {
            return $('body').outerWidth() > $(window).width();
        },

        /**
         * Try to calculate the scrollbar width for your browser/os
         * @return {Number}
         */
        scrollbarWidth: function() {
            return scrollHelper.scrollbarWidth();
        }
    };

    return layout;
});
