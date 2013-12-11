/*global define*/
/*jshint browser: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');

    var app = require('oro/app');

    var scrollspy = {};

    scrollspy.init = function (container) {
        if (app.isMobile()) {
            scrollspy._replaceWithCollapse(container);
            return;
        }

        $('.scrollspy .responsive-section:nth-of-type(1) .scrollspy-title').css('display', 'none');

        container.find('[data-spy="scroll"]').each(function () {
            var $spy = $(this);
            $spy.scrollspy($spy.data());
            $(this).scrollspy('refresh');
            $('.scrollspy-nav ul.nav li').removeClass('active');
            $('.scrollspy-nav ul.nav li:first').addClass('active');
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
                    $header.addClass('collapsed')
                } else {
                    $target.addClass('in');
                }
            });
        });
    };

    scrollspy.adjust = function () {
        if (app.isMobile()) {
            return;
        }

        $('[data-spy="scroll"]').each(function () {
            var $spy = $(this);
            var spyHeight = $spy.innerHeight();

            var isMultipleRows = $spy.find('.responsive-section').length > 1;

            $spy.find('.responsive-section:last').each(function () {
                var $row = $(this);
                var titleHeight = $row.find('.scrollspy-title').outerHeight();
                var rowAdjHeight = isMultipleRows ? titleHeight + spyHeight : spyHeight;

                var rowOrigHeight = $row.data('originalHeight');
                if (!rowOrigHeight) {
                    rowOrigHeight = $row.height();
                    $row.data('originalHeight', rowOrigHeight);
                }

                if ($row.height() === rowAdjHeight) {
                    return;
                }

                if (rowAdjHeight < rowOrigHeight) {
                    rowAdjHeight = rowOrigHeight;
                }

                $row.outerHeight(rowAdjHeight);
            });

            $spy.scrollspy('refresh');
        });
    };

    scrollspy.top = function () {
        if (app.isMobile()) {
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
