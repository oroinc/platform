/*global define*/
/*jslint nomen: true*/
/*jshint browser: true, devel: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var bootstrap = require('bootstrap');

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

        this.initPopover($('form label'));
        widgetControlInitializer.init(container);

//        @todo: BAP-3374
//        layout.onPageRendered(function () {
//            scrollspy.top();
//        });
    };

    layout.initPopover = function (container) {
        container.find('[data-toggle="popover"]')
            .popover({
                animation: false,
                delay: { show: 0, hide: 0 },
                html: true,
                trigger: 'click'
            });

        $('body').on('click', function (e) {
            $('[data-toggle="popover"]').each(function () {
                //the 'is' for buttons that trigger popups
                //the 'has' for icons within a button that triggers a popup
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                    $(this).popover('hide');
                }
            });
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
