/*global define, window*/
/*jslint nomen: true*/
/*jshint browser:true, devel:true*/
define(function (require) {
    'use strict';

    var layout, pageRenderedCbPool, document, console,
        $ = require('jquery'),
        _ = require('underscore'),
        bootstrap = require('bootstrap'),
        __ = require('orotranslation/js/translator'),
        scrollspy = require('oroui/js/scrollspy'),
        mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools');
    require('jquery-ui');
    require('jquery.uniform');
    require('oroui/js/responsive-jquery-widget');

    document = window.document;
    console = window.console;
    pageRenderedCbPool = [];

    layout = {
        /**
         * Default padding to keep when calculate available height for fullscreen layout
         */
        PAGE_BOTTOM_PADDING: 10,

        /**
         * Height of header on mobile devices
         */
        MOBILE_HEADER_HEIGHT: 54,

        /**
         * Height of header on mobile devices
         */
        MOBILE_POPUP_HEADER_HEIGHT: 44,

        /**
         * Minimal height for fullscreen layout
         */
        minimalHeightForFullScreenLayout: 300,

        /**
         * Keeps calculated devToolbarHeight. Please use getDevToolbarHeight() to retrieve it
         */
        devToolbarHeight: undefined,

        /**
         * @returns {number} development toolbar height in dev mode, 0 in production mode
         */
        getDevToolbarHeight: function () {
            if (!this.devToolbarHeight) {
                var devToolbarComposition = mediator.execute('composer:retrieve', 'debugToolbar', true);
                if (devToolbarComposition && devToolbarComposition.view) {
                    this.devToolbarHeight = devToolbarComposition.view.$el.height();
                } else {
                    this.devToolbarHeight = 0;
                }
            }
            return this.devToolbarHeight;
        },

        /**
         * Initializes
         *  - form widgets (uniform)
         *  - tooltips
         *  - popovers
         *  - scrollspy
         *  - page components
         *
         * @param {string|HTMLElement|jQuery.Element} container
         * @param {Backbone.View|Chaplin.View} parentView
         * @returns {jQuery.Promise}
         */
        init: function (container, parentView) {
            var promise, $container;

            if (!parentView) {
                throw new Error('Parent view is required for page components initialization');
            }

            $container = $(container);
            this.styleForm($container);

            scrollspy.init($container);

            $container.find('[data-toggle="tooltip"]').tooltip();

            this.initPopover($container.find('label'));

            promise = this.initPageComponents($container, parentView);
            this._bindContainerChanges($container, parentView);

            return promise;
        },

        /**
         * Initializes components defined in HTML of the container
         * and attaches them to passed parent view instance
         *
         * @param {jQuery.Element} $container
         * @param {Backbone.View|Chaplin.View} parentView
         * @returns {jQuery.Promise}
         */
        initPageComponents: function ($container, parentView) {
            var loadPromises, initDeferred, $pageComponentNodes, preloadQueue;
            // console.groupCollapsed('container', $container.attr('class'), {html: $container.clone().html('')[0].outerHTML});
            loadPromises = [];
            initDeferred = $.Deferred();
            $pageComponentNodes = $container.find('[data-page-component-module]');

            if ($pageComponentNodes.length) {
                preloadQueue = [];
                $pageComponentNodes.each(function () {
                    var $elem, module, name, options, loadDeferred, $separateLayout;

                    $elem = $(this);
                    module = $elem.data('pageComponentModule');
                    // find nearest marked container with separate layout
                    $separateLayout = $elem.closest('[data-layout="separate"]');
                    // if it placed inside container - prevent component creation from here
                    if ($separateLayout.length &&
                            $.contains($container[0], $separateLayout[0]) &&
                            this !== $separateLayout[0]) {
                        // optimize load time - push components to preload queue
                        preloadQueue.push(module);
                        return;
                    }

                    // console.log('pageComponent', $container.attr('class'), {html: $elem.clone().html('')[0].outerHTML});
                    name = $elem.data('pageComponentName');
                    options = $elem.data('pageComponentOptions') || {};
                    options._sourceElement = $elem;
                    if (name) {
                        options.name = name;
                    }
                    options.parent = parentView;

                    $elem
                        .attr('data-bound-component', module)
                        .removeData('pageComponentModule')
                        .removeData('pageComponentOptions')
                        .removeAttr('data-page-component-module')
                        .removeAttr('data-page-component-options');
                    loadDeferred = $.Deferred();

                    require([module], function (component) {
                        var result;
                        if (parentView.disposed) {
                            loadDeferred.resolve();
                            return;
                        }
                        if (typeof component.init === 'function') {
                            result = component.init(options);
                        } else {
                            result = component(options);
                        }
                        loadDeferred.resolve(result);
                    }, function (error) {
                        if (tools.debug) {
                            if (console && console.log) {
                                console.log(error.stack);
                            }
                            throw error;
                        } else {
                            // prevent interface from blocking by loader in production mode
                            mediator.execute('showMessage', 'error',
                                __('Cannot load module ') + '"' + error.requireModules[0] + '"'
                            );
                            loadDeferred.resolve();
                        }
                    });

                    loadPromises.push(loadDeferred.promise());
                });

                // optimize load time - preload components in separate layouts
                require(preloadQueue, _.noop);

                $.when.apply($, loadPromises).always(function () {
                    var initPromises = _.flatten(_.toArray(arguments), true);
                    $.when.apply($, initPromises).always(function () {
                        var components = _.compact(_.flatten(_.toArray(arguments), true));
                        initDeferred.resolve(components);
                    });
                });
            } else {
                initDeferred.resolve();
            }
            // console.groupEnd();
            return initDeferred.promise();
        },

        /**
         * Subscribes to the container changes events
         *  - on 'content:changed' event -- updates layout
         *  - on 'content:remove' event -- disposes related components (if they are left undisposed)
         *
         * @param {jQuery.Element} $container
         * @param {Backbone.View|Chaplin.View} parentView
         * @protected
         */
        _bindContainerChanges: function ($container, parentView) {
            var namespace = '.init-components',
                parentViewDataKey = 'pageComponentRelatedView',
                _parentView = $container.data(parentViewDataKey);

            if (_parentView && _parentView !== parentView) {
                throw new Error('Attempt to init container with another parent view');

            } else if (!_parentView) {
                $container.data(parentViewDataKey, parentView);

                // if the container catches content changed event -- updates its layout
                $container.on('content:changed' + namespace, function (e) {
                    if (e.isDefaultPrevented()) {
                        return;
                    }
                    e.preventDefault();
                    layout.init($container, parentView);
                });

                // if the container catches content remove event -- disposes related components
                $container.on('content:remove' + namespace, function (e) {
                    if (e.isDefaultPrevented()) {
                        return;
                    }
                    e.preventDefault();
                    $(e.target).find('[data-bound-component]').each(function () {
                        var $elem = $(this),
                            component = $elem.data('componentInstance');
                        if (component) {
                            component.dispose();
                        }
                    });
                });

                // once parent view gets disposed -- break the container bound
                parentView.once('dispose', function () {
                    $container.removeData(parentViewDataKey);
                    $container.off(namespace);
                });
            }
        },

        initPopover: function (container) {
            var $items = container.find('[data-toggle="popover"]').filter(function () {
                // skip already initialized popovers
                return !$(this).data('popover');
            });
            $items.not('[data-close="false"]').each(function (i, el) {
                //append close link
                var content = $(el).data('content');
                content += '<i class="icon-remove popover-close"></i>';
                $(el).data('content', content);
            });

            $items.popover({
                animation: false,
                delay: { show: 0, hide: 0 },
                html: true,
                container: false,
                trigger: 'manual'
            }).on('click.popover', function (e) {
                $(this).popover('toggle');
                e.preventDefault();
            });

            $('body')
                .on('click.popover-hide', function (e) {
                    var $target = $(e.target);
                    $items.each(function () {
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
                }).on('click.popover-prevent', '.popover', function (e) {
                    if (e.target.tagName.toLowerCase() !== 'a') {
                        e.preventDefault();
                    }
                }).on('focus.popover-hide', 'select, input, textarea', function () {
                    $items.popover('hide');
                });
            mediator.once('page:request', function () {
                $('body').off('.popover-hide .popover-prevent');
            });
        },

        hideProgressBar: function () {
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
        styleForm: function ($container) {
            var $elements;
            if ($.isPlainObject($.uniform)) {
                // bind uniform plugin to select elements
                $elements = $container.find('select:not(.no-uniform,.select2)');
                $elements.uniform();
                if ($elements.is('.error:not([multiple])')) {
                    $elements.removeClass('error').closest('.selector').addClass('error');
                }

                // bind uniform plugin to input:file elements
                $elements = $container.find('input:file');
                $elements.uniform({
                    fileDefaultHtml: __('Please select a file...'),
                    fileButtonHtml: __('Choose File')
                });
                if ($elements.is('.error')) {
                    $elements.removeClass('error').closest('.uploader').addClass('error');
                }
            }
        },

        /**
         * Removes forms widget and plugins from elements
         *
         * @param {jQuery=} $container
         */
        unstyleForm: function ($container) {
            var $elements;

            // removes uniform plugin from elements
            if ($.isPlainObject($.uniform)) {
                $elements = $container.find('select:not(.no-uniform,.select2)');
                $.uniform.restore($elements);
            }

            // removes select2 plugin from elements
            $container.find('.select2-container').each(function () {
                var $this = $(this);
                if ($this.data('select2')) {
                    $this.select2('destroy');
                }
            });
        },

        onPageRendered: function (cb) {
            if (document.pageReady) {
                _.defer(cb);
            } else {
                pageRenderedCbPool.push(cb);
            }
        },

        pageRendering: function () {
            document.pageReady = false;

            pageRenderedCbPool = [];
        },

        pageRendered: function () {
            document.pageReady = true;

            _.each(pageRenderedCbPool, function (cb) {
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
         * @returns {number}
         */
        getAvailableHeight: function ($mainEl) {
            var $parents = $mainEl.parents(),
                documentHeight = $(document).height(),
                heightDiff = documentHeight - $mainEl[0].getBoundingClientRect().top;
            $parents.each(function () {
                heightDiff += this.scrollTop;
            });
            return heightDiff - this.getDevToolbarHeight() - this.PAGE_BOTTOM_PADDING;
        },

        /**
         * Returns name of preferred layout for $mainEl
         *
         * @param $mainEl
         * @returns {string}
         */
        getPreferredLayout: function ($mainEl) {
            if (!this.hasHorizontalScroll() && !tools.isMobile()
                && this.getAvailableHeight($mainEl) > this.minimalHeightForFullScreenLayout) {
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
        disablePageScroll: function ($mainEl) {
            var $scrollableParents = $mainEl.parents();
            $scrollableParents.scrollTop(0);
            $scrollableParents.addClass('disable-scroll');
        },

        /**
         * Enables ability to scroll of $mainEl's scrollable parents
         *
         * @param $mainEl
         * @returns {string}
         */
        enablePageScroll: function ($mainEl) {
            $mainEl.parents().removeClass('disable-scroll');
        },

        /**
         * Returns true if page has horizontal scroll
         * @returns {boolean}
         */
        hasHorizontalScroll: function () {
            return $('body').outerWidth() > $(window).width();
        },

        /**
         * Try to calculate the scrollbar width for your browser/os
         * @return {Number}
         */
        scrollbarWidth: function () {
            if (!this._scrollbarWidth) {
                var $div = $( //borrowed from anti-scroll
                    '<div style="width:50px;height:50px;overflow-y:scroll;'
                        + 'position:absolute;top:-200px;left:-200px;"><div style="height:100px;width:100%">'
                        + '</div>'
                );
                $('body').append($div);
                var w1 = $div.innerWidth();
                var w2 = $('div', $div).innerWidth();
                $div.remove();
                this._scrollbarWidth =  w1 - w2;
            }
            return this._scrollbarWidth;
        }
    };

    return layout;
});
