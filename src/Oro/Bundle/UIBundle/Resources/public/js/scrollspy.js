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
            $spy.addClass('accordion');

            $spy.find('.responsive-section').each(function () {
                var $section = $(this);
                $section.removeClass('responsive-section').addClass('accordion-group');

                var blockId = $section.find('.scrollspy-nav-target').attr('id');
                var sel = '#' + blockId + ' + .row-fluid';
                var href = '#' + blockId + ' + .accordion-body';

                $(sel).removeClass('row-fluid').addClass('accordion-body collapse');

                var $toggle = $section.find('.scrollspy-title');
                var $link = $('<a class="accordion-toggle" data-toggle="collapse"></a>')
                    .data('parent', '[data-spy="scroll"]').attr('href', href);
                $toggle.wrap($link);
                $toggle.prepend('<i class="icon-fixed-width icon-caret-right"></i>');
            });

            $spy.collapse();

            $spy.on('shown', function (e) {
                var $i = $(e.target).prevAll('a.accordion-toggle');
                $i.find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
            });

            $spy.on('hidden', function (e) {
                var $i = $(e.target).prevAll('a.accordion-toggle');
                $i.find('i').removeClass('icon-caret-down').addClass('icon-caret-right');
            });

            $spy.find('.collapse').first().collapse('show');
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
