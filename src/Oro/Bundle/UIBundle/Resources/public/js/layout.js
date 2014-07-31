/*global define*/
/*jslint nomen: true*/
/*jshint browser: true, devel: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var bootstrap = require('bootstrap');
    var __ = require('orotranslation/js/translator');

    var scrollspy = require('oroui/js/scrollspy');
    var widgetControlInitializer = require('oroui/js/widget-control-initializer');
    var mediator = require('oroui/js/mediator');

    var pageRenderedCbPool = [];
    var layout = {};

    require('jquery-ui');
    require('jquery-ui-timepicker');

    layout.init = function (container) {
        container = $(container || document.body);
        this.styleForm(container);

        scrollspy.init(container);

        container.find('[data-toggle="tooltip"]').tooltip();

        this.initPopover(container.find('form label'));
        widgetControlInitializer.init(container);

//        @todo: BAP-3374
//        layout.onPageRendered(function () {
//            scrollspy.top();
//        });
    };

    layout.initPopover = function (container) {
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
                $items.each(function () {
                    //the 'is' for buttons that trigger popups
                    //the 'has' for icons within a button that triggers a popup
                    if (!$(this).is(e.target)
                        && $(this).has(e.target).length === 0
                        && ($('.popover').has(e.target).length === 0 || ~e.target.className.indexOf('popover-close'))) {
                        $(this).popover('hide');
                    }
                });
            }).on('click.popover-prevent', '.popover', function(e) {
                if (e.target.tagName.toLowerCase() != 'a') {
                    e.preventDefault();
                }
            }).on('focus.popover-hide', 'select, input, textarea', function() {
                $items.popover('hide');
            });
        mediator.once('page:request', function () {
            $('body').off('.popover-hide .popover-prevent');
        });
    };

    layout.hideProgressBar = function () {
        var $bar = $('#progressbar');
        if ($bar.is(':visible')) {
            $bar.hide();
            $('#page').show();
        }
    };

    layout.styleForm = function (container) {
        if ($.isPlainObject($.uniform)) {
            var selectElements = $(container).find('select:not(.select2)');
            selectElements.uniform();

            var fileElements = $(container).find('input:file');
            fileElements.uniform({fileDefaultHtml: __('Please select a file...')});

            selectElements.trigger('uniformInit');
            fileElements.trigger('uniformInit');
        }
    };

    layout.onPageRendered = function (cb) {
        if (document.pageReady) {
            setTimeout(cb, 0);
        } else {
            pageRenderedCbPool.push(cb);
        }
    };

    layout.pageRendering = function () {
        document.pageReady = false;

        pageRenderedCbPool = [];
    };

    layout.pageRendered = function () {
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
    };

    mediator.on('layout.init', function(element) {
        layout.init(element);
    });

    mediator.on('grid_load:complete', function(collection, element) {
        widgetControlInitializer.init(element);
    });

    mediator.on('grid_render:complete', function(element) {
        widgetControlInitializer.init(element);
    });

    return layout;
});
