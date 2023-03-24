define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const scrollspy = require('oroui/js/scrollspy');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const scrollHelper = require('oroui/js/tools/scroll-helper');
    const Popover = require('bootstrap-popover');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    require('jquery-ui/tabbable');

    const document = window.document;
    const console = window.console;
    let pageRenderedCbPool = [];

    const ESCAPE_KEY_CODE = 27;

    const layout = {
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
         * List of elements with disabled scroll. Used to reset theirs state
         * @private
         */
        _scrollDisabledElements: null,

        /**
         * Initializes form widgets, scrollspy, and triggers `initLayout` event
         *
         * @param {string|HTMLElement|jQuery.Element} container
         */
        init: function(container) {
            const $container = $(container);

            this.styleForm($container);

            scrollspy.init($container);

            $container.trigger('initLayout');

            $container.on({
                'content:changed': this.onContentChanged,
                'content:remove': this.onContentRemove
            });
        },

        initPopover: function(container, options) {
            const $items = container.find('[data-toggle="popover"]')
                // skip already initialized popovers
                .filter((i, elem) => !$(elem).data(Popover.DATA_KEY));

            if ($items.length) {
                this.initPopoverForElements($items, options);
            }
        },

        initPopoverForElements: function($items, options, overrideOptionsByData) {
            options = _.defaults(options || {}, {
                animation: false,
                delay: {show: 0, hide: 0},
                html: true,
                container: false,
                trigger: 'manual',
                forceToShowTitle: false
            });

            if (overrideOptionsByData) {
                options = $.extend(options, $items.data());
            }
            if (options.close !== false) {
                $items.not('[data-close="false"]').each((i, el) => {
                    // append close link
                    let content = el.getAttribute('data-content');
                    content += '<i class="fa-close popover-close" aria-hidden="true"></i>';
                    el.setAttribute('data-content', content);
                });
            }

            const popoverConfig = _.omit(options, 'forceToShowTitle');

            $items.popover(popoverConfig).on('click' + Popover.EVENT_KEY, function(e) {
                if ($(this).is('.disabled, :disabled')) {
                    return;
                }

                $(this).popover('toggle');

                e.preventDefault();
            });

            if (options.forceToShowTitle) {
                $items
                    .on('mouseenter' + Popover.EVENT_KEY, e => {
                        // Element is not disabled, title is cropped and and the popover is not opened
                        if (
                            !$(e.target).is('[disabled]') &&
                            $(e.target).is('[data-original-title]') &&
                            !$(e.target).is('[aria-describedby]')
                        ) {
                            $(e.target).attr('title', $(e.target).attr('data-original-title'));
                        }
                    })
                    .on('mouseleave' + Popover.EVENT_KEY, e => {
                        // Element is not disabled, title is cropped
                        if (
                            !$(e.target).is('[disabled]') &&
                            $(e.target).is('[data-original-title]')
                        ) {
                            $(e.target).attr('title', '');
                        }
                    });
            }

            $items
                .on(`shown${Popover.EVENT_KEY}`, e => {
                    const popover = $(e.target).data(Popover.DATA_KEY);
                    const $tip = $(popover.tip);
                    const $tabbable = $tip.find(':tabbable').eq(0);

                    if (!$tabbable.length) {
                        return;
                    }

                    const config = popover._getConfig();
                    let timeout = 0;

                    if (config.animation) {
                        timeout = config.delay.show;
                    }

                    setTimeout(() => {
                        manageFocus.focusTabbable($(popover.getTipElement()), $tabbable);

                        $tip.on('keydown' + Popover.EVENT_KEY,
                            e => manageFocus.preventTabOutOfContainer(e, $tip));

                        $(e.target).one('hide' + Popover.EVENT_KEY, e => $tip.off(Popover.EVENT_KEY));
                    }, timeout);
                })
                .on(`hide${Popover.EVENT_KEY}`, e => {
                    const popover = $(e.target).data(Popover.DATA_KEY);

                    if ($.contains(popover.tip, document.activeElement)) {
                        $(e.target).trigger('focus');
                    }
                })
                .on(`focusout${Popover.EVENT_KEY}`, e => {
                    const popover = $(e.target).data(Popover.DATA_KEY);

                    if (
                        popover &&
                        popover.isOpen() &&
                        !$.contains(popover.tip, e.relatedTarget)
                    ) {
                        $(e.target).popover('hide');
                    }
                });

            $(document).on('keydown.popover-hide', e => {
                if (e.keyCode === ESCAPE_KEY_CODE) {
                    $items.filter('[aria-describedby]').each(function() {
                        $(this).popover('hide');
                    });
                }
            });

            $('body')
                .on('click.popover-hide', function(e) {
                    const $target = $(e.target);
                    // '[aria-describedby]' -- means that the popover is opened.
                    $items.filter('[aria-describedby]').each(function() {
                        // the 'is' for buttons that trigger popups
                        // the 'has' for icons within a button that triggers a popup
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
                    // '[aria-describedby]' -- means that the popover is opened.
                    $items.filter('[aria-describedby]').popover('hide');
                });
            mediator.once('page:request', function() {
                $(document).off('.popover-hide');
                $('body').off('.popover-hide .popover-prevent');
            });
        },

        /**
         * Disposes form widgets and triggers `disposeLayout` event
         *
         * @param {string|HTMLElement|jQuery.Element} container
         */
        dispose: function(container) {
            const $container = $(container);

            $container.off({
                'content:changed': this.onContentChanged,
                'content:remove': this.onContentRemove
            });

            this.unstyleForm($container);

            $container.trigger('disposeLayout');
        },

        hideProgressBar: function() {
            const $bar = $('#progressbar');
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
         * Returns available height for element if page will be transformed to fullscreen mode
         *
         * @param $mainEl
         * @param boundingClientRect - pass boundingClientRect of $mainEl to avoid it expensive calculation
         * @returns {number}
         */
        getAvailableHeight: function($mainEl, boundingClientRect) {
            const $parents = $mainEl.parents();
            const documentHeight = scrollHelper.documentHeight();
            let heightDiff = documentHeight -
                (boundingClientRect ? boundingClientRect : $mainEl[0].getBoundingClientRect()).top;
            $parents.each(function() {
                heightDiff += this.scrollTop;
            });
            const parentDialogWidgetElem = $mainEl.closest('.ui-dialog-content .widget-content')[0];
            if (parentDialogWidgetElem) {
                heightDiff -= documentHeight - parentDialogWidgetElem.getBoundingClientRect().bottom;
            } else {
                heightDiff -= documentHeight - $('#container')[0].getBoundingClientRect().bottom;
                heightDiff -= this.PAGE_BOTTOM_PADDING;
            }
            return heightDiff;
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
         * Returns root application element
         *
         * @return {HTMLElement}
         */
        getRootElement() {
            return document.getElementById('page');
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
            const $scrollableParents = $mainEl.parents();
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
                this._scrollDisabledElements.removeClass('disable-scroll');
                delete this._scrollDisabledElements;
            }
        },

        /**
         * Returns true if page has horizontal scroll
         * @returns {boolean}
         */
        hasHorizontalScroll: function() {
            return Math.round($('body').outerWidth()) > $(window).width();
        },

        /**
         * Try to calculate the scrollbar width for your browser/os
         * @return {Number}
         */
        scrollbarWidth: function() {
            return scrollHelper.scrollbarWidth();
        },

        onContentChanged: function(e) {
            if (e.isLayoutProcessed) {
                return;
            }
            const $target = $(e.target);
            layout.styleForm($target);
            $target.trigger('initLayout');
            e.isLayoutProcessed = true;
        },

        onContentRemove: function(e) {
            if (e.isLayoutProcessed) {
                return;
            }
            const $target = $(e.target);
            $target.trigger('disposeLayout');
            layout.unstyleForm($target);
            e.isLayoutProcessed = true;
        },

        /**
         * Adjust width for form labels into dialog form
         * @private
         */
        adjustLabelsWidth: function($context) {
            const controlGroups = $context.find('.control-group').filter(function(i, group) {
                return !$(group).find('> .control-label').length && !$(group).closest('.tab-content, .controls').length;
            });
            const labels = $context.find('.control-label').filter(function(i, label) {
                return !$(label).closest('.widget-title-container, .attribute-item__description').length;
            });

            labels.css('width', '');

            const width = labels.map(function(i, label) {
                return label.getBoundingClientRect().width;
            }).get();

            const newWidth = Math.ceil(Math.max.apply(null, width));
            labels.css('width', newWidth);

            controlGroups.each(function(i, group) {
                const prop = 'margin-' + (_.isRTL() ? 'right' : 'left');
                const controls = $(group).find('> .controls');
                controls
                    .css(prop, '')
                    .css(prop, parseInt(controls.css(prop)) + newWidth);
            });
        }
    };

    return layout;
});
