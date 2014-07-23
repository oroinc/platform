/*global define*/
/*jshint browser: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');

    var tools = require('oroui/js/tools');
    var mediator = require('oroui/js/mediator');

    var scrollspy = {};

    scrollspy.init = function (container) {
        if (tools.isMobile()) {
            scrollspy._replaceWithCollapse(container);
            return;
        }

        $('.scrollspy .responsive-section:nth-of-type(1) .scrollspy-title').css('display', 'none');

        container.find('[data-spy="scroll"]').each(function () {
            var $spy = $(this);
            $spy.scrollspy($spy.data());
        });
    };

    scrollspy._replaceWithCollapse = function (container) {
        container.find('[data-spy="scroll"]').each(function () {
            var $spy = $(this);
            $spy.removeAttr('data-spy').addClass('accordion');

            $spy.find('.scrollspy-title').each(function (i) {
                var $header = $(this),
                    targetSelector = '#' + $header.next().attr('id') + '+',
                    $target = $(targetSelector);
                $header
                    .removeClass('scrollspy-title')
                    .addClass('accordion-toggle')
                    .attr({
                        'data-toggle': 'collapse',
                        'data-target': targetSelector
                    });
                $header.parent().addClass('accordion-group');
                $target.addClass('accordion-body collapse');
                $header.wrap('<div class="accordion-heading"></div>');
                // first is opened, rest are closed
                if (i > 0) {
                    $header.addClass('collapsed');
                } else {
                    $target.addClass('in').data('toggle', false);
                }
                $target.on('focusin', function () {
                    $target.collapse('show');
                });
            });
        });
    };

    scrollspy.adjust = function () {
        if (tools.isMobile()) {
            return;
        }

        $('[data-spy="scroll"]').each(function () {
            var $spy = $(this);
            var spyHeight = $spy.innerHeight();
            var debugBarHeight = $('.sf-toolbar').height() || 0;

            var isMultipleRows = $spy.find('.responsive-section').length > 1;

            $spy.find('.responsive-section:last').each(function () {
                var $row = $(this);
                var titleHeight = $row.find('.scrollspy-title').outerHeight();
                var rowAdjHeight = (isMultipleRows ? titleHeight + spyHeight : spyHeight) - debugBarHeight;
                var naturalHeight = $row.height('auto').height();

                if (rowAdjHeight > naturalHeight) {
                    $row.outerHeight(rowAdjHeight);
                }
            });

            if ($spy.data('scrollspy')) {
                $spy.scrollspy('refresh').scrollspy('process');
            }
        });
    };

    scrollspy.top = function () {
        if (tools.isMobile()) {
            return;
        }

        $('[data-spy="scroll"]').each(function () {
            var $spy = $(this);
            var targetSelector = $spy.data('target');
            var target = $(targetSelector);

            target.each(function () {
                var $target = $(this);
                var firstItemHref = $target.find('li.active:first a').attr('href');
                var $firstItem = $(firstItemHref);
                var top = $firstItem.position().top;

                $spy.scrollTop(top);
            });
        });
    };

    return scrollspy;
});
