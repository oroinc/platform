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
        widgetControlInitializer = require('oroui/js/widget-control-initializer'),
        mediator = require('oroui/js/mediator'),
        tools = require('oroui/js/tools');
    require('jquery-ui');
    require('jquery-ui-timepicker');
    require('jquery.uniform');

    document = window.document;
    console = window.console;
    pageRenderedCbPool = [];

    layout = {
        init: function (container) {
            var promise;
            container = $(container);
            this.styleForm(container);

            scrollspy.init(container);

            container.find('[data-toggle="tooltip"]').tooltip();

            this.initPopover(container.find('form label'));
            widgetControlInitializer.init(container);

            promise = this.initPageComponents(container);
            return promise;
        },

        initPageComponents: function (container) {
            var loads, initialized;
            loads = [];
            initialized = $.Deferred();

            container.find('[data-page-component-module]').each(function () {
                var $elem, module, options, loaded;

                $elem = $(this);
                module = $elem.data('pageComponentModule');
                options = $elem.data('pageComponentOptions');
                options._sourceElement = $elem;
                $elem
                    .removeData('pageComponentModule')
                    .removeData('pageComponentOptions')
                    .removeAttr('data-page-component-module')
                    .removeAttr('data-page-component-options');
                loaded = $.Deferred();

                require([module], function (component) {
                    if (typeof component.init === "function") {
                        loaded.resolve(component.init(options));
                    } else {
                        loaded.resolve(component(options));
                    }
                }, function () {
                    loaded.resolve();
                });

                loads.push(loaded.promise());
            });

            $.when.apply($, loads).always(function () {
                var initializes = _.flatten(_.toArray(arguments), true);
                $.when.apply($, initializes).always(function () {
                    var components = _.compact(_.flatten(_.toArray(arguments), true));
                    initialized.resolve(components);
                });
            });

            return initialized.promise();
        },

        initPopover: function (container) {
            var $items = container.find('[data-toggle="popover"]');
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
                $elements = $container.find('select:not(.select2)');
                $elements.uniform();
                if ($elements.is('.error:not([multiple])')) {
                    $elements.removeClass('error').closest('.selector').addClass('error');
                }

                // bind uniform plugin to input:file elements
                $elements = $container.find('input:file');
                $elements.uniform({fileDefaultHtml: __('Please select a file...')});
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
                $elements = $container.find('select:not(.select2)');
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
        }
    };

    mediator.on('grid_load:complete', function (collection, element) {
        widgetControlInitializer.init(element);
    });

    mediator.on('grid_render:complete', function (element) {
        widgetControlInitializer.init(element);
    });

    return layout;
});
