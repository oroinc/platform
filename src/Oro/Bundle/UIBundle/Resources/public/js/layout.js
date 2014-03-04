/*global define*/
/*jslint nomen: true*/
/*jshint browser: true, devel: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');

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

        var handlePopoverMouseout = function (e, popover) {
            var popoverHandler = $(e.relatedTarget).closest('.popover');
            if (!popoverHandler.length) {
                popover.data('popover-timer',
                    setTimeout(function () {
                        popover.popover('hide');
                        popover.data('popover-active', false);
                    }, 500));
            } else {
                popoverHandler.one('mouseout', function (evt) {
                    handlePopoverMouseout(evt, popover);
                });
            }
        };
        $('form label [data-toggle="popover"]')
            .popover({
                animation: true,
                delay: { show: 0, hide: 0 },
                html: true,
                trigger: 'manual'
            })
            .mouseover(function () {
                var popoverEl = $(this);
                clearTimeout(popoverEl.data('popover-timer'));
                if (!popoverEl.data('popover-active')) {
                    popoverEl.data('popover-active', true);
                    $(this).popover('show');
                }
            })
            .mouseout(function (e) {
                var popover = $(this);
                setTimeout(function () {
                    handlePopoverMouseout(e, popover);
                }, 500);
            });

        widgetControlInitializer.init(container);
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
            var elements = $(container).find('input:file, select:not(.select2)');
            elements.uniform();
            elements.trigger('uniformInit');
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
