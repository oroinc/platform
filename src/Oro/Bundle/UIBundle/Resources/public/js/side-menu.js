define(['jquery', 'backbone', './mediator', 'jquery-ui'], function($, Backbone, mediator) {
    'use strict';

    $.widget('oroui.sideMenu', {
        options: {
            menuPrefix: 'main-menu-group',
            rootElement: '.main-menu',
            toggleSelector: '',
            autoCollapse: true
        },

        /**
         * Creates side menu
         *
         * @private
         */
        _create: function() {
            this.listener = $.extend({}, Backbone.Events);
            this._on({mainMenuUpdated: this._init});

            this.$toggle = $(this.options.toggleSelector);
            this._on(this.$toggle, {click: this.onToggle});
        },

        /**
         * Destroys widget's references
         *
         * @private
         */
        _destroy: function() {
            this.listener.stopListening(mediator);
        },

        /**
         * Do initial changes
         *
         * @private
         */
        _init: function() {
            // should be implemented in descendant
        },

        /**
         * Converts menu's markup from dropdown to accordion
         *
         * @private
         */
        _convertToAccordion: function() {
            var $root = this.element.find(this.options.rootElement).first();
            $root.attr('id', this._getGroupId(0)).addClass('accordion');
            var $groups = $root.find('.dropdown');

            $root.find('.dropdown-menu').removeClass('dropdown-menu').addClass('accordion-body collapse');
            $root.find('.dropdown-menu-wrapper').removeClass('hidden');
            $root.find('.dropdown-menu-wrapper__scrollable').css({'max-height': 'none'});
            $root.find('.dropdown-menu-wrapper__child').css({'margin-left': 0, 'margin-top': 0});
            $groups.removeClass('dropdown').addClass('accordion-group');

            var self = this;
            $groups.add($root).each(function(i) {
                var $group = $(this);
                var isActive = $group.hasClass('active');
                var autoCollapse = self.options.autoCollapse;
                var $header = $group.find('a>span').first();
                var $target = $group.find('.accordion-body').first();
                var headerId = self._getGroupId(i + 1) + '-header';
                var targetId = self._getGroupId(i + 1);

                $header.addClass('accordion-toggle')
                    .toggleClass('collapsed', !isActive)
                    .attr({
                        'id': headerId,
                        'data-toggle': 'collapse',
                        'data-target': '#' + targetId,
                        'aria-controls': targetId,
                        'aria-expanded': isActive
                    })
                    .closest('a').addClass('accordion-heading');

                $target.attr({
                    'id': targetId,
                    'role': 'menu',
                    'data-parent': autoCollapse ? '#' + $header.closest('.accordion').attr('id') : null,
                    'aria-labelledby': headerId
                }).toggleClass('show', isActive);

                if ($target.has('.accordion-group')) {
                    $target.addClass('accordion');
                }
            });
        },

        /**
         * Converts menu's markup from accordion to dropdown
         *
         * @private
         */
        _convertToDropdown: function() {
            this.element.find('.accordion').removeClass('accordion collapsed');
            this.element.find('.accordion-body')
                .removeClass('accordion-body collapse show')
                .removeAttr('id style role aria-labelledby')
                .addClass('dropdown-menu');
            this.element.find('.accordion-group').removeClass('accordion-group').addClass('dropdown');
            this.element.find('.accordion-toggle')
                .removeClass('accordion-toggle collapsed')
                .removeAttr('id data-toggle data-target data-parent aria-controls aria-expanded');
            this.element.find('.accordion-heading').removeClass('accordion-heading');
        },

        /**
         * Handles menu toggle state action
         *
         * @param {jQuery.Event} e
         */
        onToggle: function(e) {
            e.stopPropagation();
            this._toggle();
        },

        /**
         * Implements toggling process
         */
        _toggle: function() {
            // should be implemented in descendant
        },

        /**
         * Generates id value for sub-menu group
         *
         * @param {number} i
         * @returns {string}
         * @private
         */
        _getGroupId: function(i) {
            return this.options.menuPrefix + '_' + i;
        }
    });

    return $;
});
