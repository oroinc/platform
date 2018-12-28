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

        $container.find('[data-spy="scroll"]').each(function() {
            var $spy = $(this);

            if (tools.isDesktop()) {
                $spy.find('.responsive-section:last').each(function() {
                    var $section = $(this);
                    var titleHeight = $section.find('.scrollspy-title:visible').outerHeight();
                    $section.css('min-height', 'calc(100% + ' + (titleHeight || 0) + 'px)');
                });
            }

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
            var menuSelector = $spy.data('target') || href || '';
            // make target to be container related
            $spy.data('target', '#' + containerId + ' ' + menuSelector);

            container.find(menuSelector + ' .nav > a').each(function() {
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
                // first is opened, rest are closed
                var collapsed = i > 0;
                var $header = $(this);
                var $target = $header.next().next();
                var targetId = _.uniqueId('collapse-');
                var headerId = targetId + '-trigger';

                $header
                    .removeClass('scrollspy-title')
                    .addClass('accordion-toggle')
                    .toggleClass('collapsed', collapsed)
                    .attr({
                        'id': headerId,
                        'role': 'button',
                        'data-toggle': 'collapse',
                        'data-target': '#' + targetId,
                        'aria-controls': targetId,
                        'aria-expanded': !collapsed
                    })
                    .parent().addClass('accordion-group');
                $header.wrap('<div class="accordion-heading"/>');

                $target.addClass('accordion-body collapse')
                    .toggleClass('show', !collapsed)
                    .attr({
                        'id': targetId,
                        'role': 'region',
                        'aria-labelledby': headerId
                    });

                if (!collapsed) {
                    $target.data('toggle', false);
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

            if ($spy.data('bs.scrollspy')) {
                $spy.scrollspy('refresh').scrollspy('_process');
            }
        });
    };

    return scrollspy;
});
