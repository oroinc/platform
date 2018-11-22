define(['jquery', 'underscore', 'jquery-ui'], function($, _) {
    'use strict';

    /**
     * Converts buttons sequence from container to group with main buttons
     * and rest buttons in dropdown
     */
    $.widget('oroui.dropdownButtonProcessor', {
        options: {
            separator: '.separator-btn',
            includeButtons: '.btn, .divider, .dropdown-divider, .dropdown-menu>li>*',
            excludeButtons: '.dropdown-toggle',
            mainButtons: '.main-group:not(.more-group)',
            useMainButtonsClone: false,
            truncateLength: null,
            moreLabel: '',
            groupContainer: '<div class="btn-group"></div>',
            minItemQuantity: 1,
            moreButtonAttrs: {},
            decoreClass: null
        },

        group: null,

        main: null,

        dropdown: null,

        _create: function() {
            // replaces button's separators
            this.element.find(this.options.separator).replaceWith('<div class="dropdown-divider"></div>');

            this._renderButtons();
        },

        _destroy: function() {
            delete this.group;
            delete this.main;
            delete this.dropdown;
        },

        _renderButtons: function() {
            var $elems = this._collectButtons();
            if ($elems.length <= 1) {
                this._removeDropdownMenu();
                return;
            }

            this.group = $(this.options.groupContainer);

            this.main = this._mainButtons($elems);
            if (this.options.useMainButtonsClone) {
                this.main = this._prepareMainButton(this.main);
            }

            this.group.append(this.main.addClass(this.options.decoreClass || ''));

            // pushes rest buttons to dropdown
            $elems = $elems.not(this.group);
            if ($elems.length > this.options.minItemQuantity) {
                this.group.append(this._moreButton());
                $elems = this.dropdown = this._dropdownMenu($elems);
            }
            this.group.append($elems);

            this.element.find('.btn-group').remove().end().prepend(this.group);
        },

        /**
         * Checks if the button processor has grouped buttons
         *
         * @return {boolean}
         */
        isGrouped: function() {
            return Boolean(this.group);
        },

        /**
         * Collects all buttons of the container
         *
         * @param {jQuery|null} $element
         * @returns {*}
         * @private
         */
        _collectButtons: function($element) {
            return ($element || this.element)
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
        _mainButtons: function($buttons) {
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
        _moreButton: function() {
            var $button = $('<a href="#"/>');
            $button
                .attr($.extend({
                    'role': 'button',
                    'data-toggle': 'dropdown',
                    'data-placement': 'bottom-end',
                    'data-inherit-parent-width': 'loosely'
                }, this.options.moreButtonAttrs))
                .addClass('btn dropdown-toggle btn-more-actions')
                .addClass(this.options.decoreClass || '')
                .append(this.options.moreLabel);

            return $button;
        },

        /**
         * Generates dropdown menu
         *
         * @param {jQuery} $buttons
         * @returns {*}
         * @private
         */
        _dropdownMenu: function($buttons) {
            return $('<ul></ul>', {
                'class': 'dropdown-menu'
            }).append(this._prepareButtons($buttons));
        },

        _removeDropdownMenu: function() {
            if (this.element.find('[data-toggle="dropdown"]').length) {
                this.element.find('[data-toggle="dropdown"], .dropdown-menu').remove();
            }
        },

        _prepareMainButton: function($main) {
            $main = $main.clone(true);
            if (this.options.truncateLength) {
                var self = this;
                // set text value string
                $main.contents().each(function() {
                    if (this.nodeType === Node.TEXT_NODE) {
                        this.nodeValue = _.trunc(this.nodeValue, self.options.truncateLength, false, '...');
                    }
                });
            }
            return $main;
        },

        _prepareButtons: function($buttons) {
            $buttons.filter(':not(.dropdown-divider)').addClass('dropdown-item');
            return $buttons.filter('.btn')
                .removeClass(function(index, css) {
                    return (css.match(/\bbtn(-\S+)?/g) || []).join(' ');
                }).wrap('<li></li>').parent();
        }
    });

    return $;
});
