define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var scrollspy = {};

    scrollspy.init = function($container) {
        if (tools.isMobile()) {
            this._replaceWithCollapse($container);
            return;
        }

        if (!$container.is('body')) {
            // if it's not main scroll-spy, make its target unique
            this.makeUnique($container);
        }

        $('.scrollspy .responsive-section:nth-of-type(1) .scrollspy-title').css('display', 'none');

        $container.find('[data-spy="scroll"]').each(function() {
            var $spy = $(this);
            $spy.scrollspy($spy.data());
        });
    };

    /**
     * Makes links and targets' ids of scroll-spy container unique
     *  - modifies scroll-spy's container target
     *  - adds ns-suffix for all links to not mix them with general scroll-spy
     *
     * @param {jQuery} container
     */
    scrollspy.makeUnique = function(container) {
        var $scrollSpy = container.find('[data-spy="scroll"]');
        if (!$scrollSpy.length) {
            // there's no scroll-spy elements
            return;
        }

        var containerId = container.attr('id');
        if (!containerId) {
            // make sure container has id
            containerId = _.uniqueId('scrollspy');
            container.attr('id', containerId);
        }

        $scrollSpy.each(function() {
            var suffix = _.uniqueId('-');
            var $spy = $(this);
            var href = $spy.attr('href');
            if (href) {
                href = href.replace(/.*(?=#[^\s]+$)/, ''); //strip for ie7
            }
            var menuSelector = $spy.data('target') || href || '';
            // make target to be container related
            $spy.data('target', '#' + containerId + ' ' + menuSelector);

            container.find(menuSelector  + ' .nav li > a').each(function() {
                var $target;
                var $link = $(this);
                var target = $link.data('target') || $link.attr('href');
                if (/^#\w/.test(target)) {
                    $target = container.find(target);
                }
                // make menu item and its target unique
                target += suffix;
                $link.attr('href', target);
                $target.attr('id', target.substr(1));
            });
        });
    };

    scrollspy._replaceWithCollapse = function(container) {
        container.find('[data-spy="scroll"]').each(function() {
            var $spy = $(this);
            $spy.removeAttr('data-spy').addClass('accordion');

            $spy.find('.scrollspy-title').each(function(i) {
                var $header = $(this);
                var targetSelector = '#' + $header.next().attr('id') + '+';
                var $target = $(targetSelector);
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
                $target.on('focusin', function() {
                    $target.collapse('show');
                });
            });
        });
    };

    scrollspy.adjust = function() {
        if (tools.isMobile()) {
            return;
        }

        $('[data-spy="scroll"]').each(function() {
            var $spy = $(this);
            var spyHeight = $spy.innerHeight();
            var isMultipleRows = $spy.find('.responsive-section').length > 1;

            $spy.find('.responsive-section:last').each(function() {
                var $row = $(this);
                var titleHeight = $row.find('.scrollspy-title').outerHeight();
                var rowAdjHeight = isMultipleRows ? titleHeight + spyHeight : spyHeight;
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

    scrollspy.top = function() {
        if (tools.isMobile()) {
            return;
        }

        $('[data-spy="scroll"]').each(function() {
            var $spy = $(this);
            var targetSelector = $spy.data('target');
            var target = $(targetSelector);

            target.each(function() {
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
