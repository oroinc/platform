/*global define*/
define(['jquery', 'jquery-ui'], function ($) {
    'use strict';

    /**
     * Converts buttons sequence from container to group with main buttons
     * and rest buttons in dropdown
     */

    $.widget('oroui.dropdownButtonProcessor', {
        options: {
            separator: '.separator-btn',
            includeButtons: '.btn, .divider, .dropdown-menu>li>*',
            excludeButtons: '.dropdown-toggle',
            mainButtons: '.main-group:not(.more-group)',
            useMainButtonsClone: false,
            moreLabel: '',
            groupContainer: '<div class="btn-group pull-right"></div>',
            minItemQuantity: 1,
            moreButtonAttrs: {}
        },

        _create: function () {
            var $elems, $main, $more,
                $group = $(this.options.groupContainer);

            // replaces button's separators
            this.element.find(this.options.separator).replaceWith('<li class="divider"></li>');

            $elems = this._collectButtons();
            if ($elems.length <= 1) {
                return;
            }

            $main = this._mainButtons($elems);
            if (this.options.useMainButtonsClone) {
                $main = $main.clone(true);
            }
            $group.append($main);

            // pushes rest buttons to dropdown
            $elems = $elems.not($main);
            if ($elems.length > this.options.minItemQuantity) {
                $more = this._moreButton();
                $group.append($more);

                $elems = this._dropdownMenu($elems);
            }
            $group.append($elems);

            this.element.find('.btn-group').remove().end().prepend($group);
        },

        /**
         * Collects all buttons of the container
         *
         * @returns {*}
         * @private
         */
        _collectButtons: function () {
            return this.element
                .find(this.options.includeButtons)
                .not(this.options.excludeButtons)
                .addClass('btn')
                .removeClass('pull-right');
        },

        /**
         * Defines main buttons
         *
         * @param {jQuery} $buttons
         * @returns {jQuery}
         * @private
         */
        _mainButtons: function ($buttons) {
            var $main = $buttons.filter(this.options.mainButtons);
            if (!$main.length) {
                $main = $buttons.first();
            }
            return $main;
        },

        /**
         * Generates "more" button
         *
         * @returns {string}
         * @private
         */
        _moreButton: function () {
            var $button = $('<a href="#"/>');
            $button
                .attr(this.options.moreButtonAttrs)
                .attr({'data-toggle': 'dropdown'})
                .addClass('btn dropdown-toggle')
                .append(this.options.moreLabel)
                .append('<span class="caret"></span>');

            return $button;
        },

        /**
         * Generates dropdown menu
         *
         * @param {jQuery} $buttons
         * @returns {*}
         * @private
         */
        _dropdownMenu: function ($buttons) {
            return $('<ul class="dropdown-menu"></ul>')
                .append($buttons)
                .find('.btn')
                .wrap('<li></li>')
                .removeClass(function (index, css) {
                    return (css.match(/\bbtn(-\S+)?/g) || []).join(' ');
                }).end();
        }
    });

    return $;
});
