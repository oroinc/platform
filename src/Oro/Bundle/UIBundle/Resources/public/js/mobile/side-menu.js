/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
define(['jquery', 'backbone', 'oro/mediator', 'jquery-ui'], function ($, Backbone, mediator) {
    'use strict';

    $.widget('oro.sideMenu', {
        options: {
            menuPrefix: 'main-menu-group',
            toggleSelector: ''
        },

        /**
         * Creates side menu
         *
         * @private
         */
        _create: function () {
            this.listener = $.extend({}, Backbone.Events);
            this.listener.listenTo(mediator, 'hash_navigation_request:refresh', $.proxy(this._init, this));
            this.listener.listenTo(mediator, 'hash_navigation_request:start', $.proxy(this._hide, this));

            this.$toggle = $(this.options.toggleSelector);
            this._on(this.$toggle, {click: this.onToggle});

            // handler for hiding menu on outside click
            this._onOutsideClick = $.proxy(function (e) {
                if (!$.contains(this.element.get(0), e.target)) {
                    this._hide();
                }
            }, this);
        },

        /**
         * Destroys widget's references
         *
         * @private
         */
        _destroy: function () {
            this.listener.stopListening(mediator);
        },

        /**
         * Adds accordion's styles for HTML of menu
         *
         * @private
         */
        _init: function () {
            var self = this;
            // root element
            self.element.children('ul').first().attr('id', self._getGroupId(0));
            $.each(this.element.find('a[href=#]>span'), function (i) {
                var $header = $(this),
                    $target = $header.parent().next('ul'),
                    targetId = self._getGroupId(i + 1),
                    $parent = $header.closest('ul'),
                    parentId = $parent.attr('id');
                $parent.addClass('accordion');
                $target.addClass('accordion-body collapse').attr('id', targetId);
                $header
                    .addClass('accordion-toggle')
                    .attr({
                        'data-toggle': 'collapse',
                        'data-target': '#' + targetId,
                        'data-parent': '#' + parentId
                    }).closest('a').addClass('accordion-heading')
                    .closest('li').addClass('accordion-group');
                if ($target.closest('li').hasClass('active')) {
                    $target.addClass('in');
                } else {
                    $header.addClass('collapsed');
                }
            });
        },

        /**
         * Performs show menu action
         *
         * @private
         */
        _show: function () {
            this.$toggle.addClass('open');
            this.element.add(this.element.next()).switchClass('', 'menu-opened', 200);
            $(document).on('click', this._onOutsideClick);
        },

        /**
         * Performs hide menu action
         *
         * @private
         */
        _hide: function () {
            this.$toggle.removeClass('open');
            this.element.add(this.element.next()).switchClass('menu-opened', '', 200);
            $(document).off('click', this._onOutsideClick);
        },

        /**
         * Handles open/close side menu
         *
         * @param {jQuery.Event} e
         */
        onToggle: function (e) {
            if (!this.$toggle.hasClass('open')) {
                this._show();
            } else {
                this._hide();
            }
            e.stopPropagation();
        },

        /**
         * Generates id value for sub-menu group
         *
         * @param {number} i
         * @returns {string}
         * @private
         */
        _getGroupId: function (i) {
            return this.options.menuPrefix + '_' + i;
        }
    });
});
