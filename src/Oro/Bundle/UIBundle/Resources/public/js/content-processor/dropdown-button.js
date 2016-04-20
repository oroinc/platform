define(['jquery', 'underscore', 'jquery-ui'], function($, _) {
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
            truncateLength: null,
            moreLabel: '',
            groupContainer: '<div class="btn-group pull-right"></div>',
            minItemQuantity: 1,
            moreButtonAttrs: {}
        },

        group: null,

        main: null,

        dropdown: null,

        _create: function() {
            // replaces button's separators
            this.element.find(this.options.separator).replaceWith('<li class="divider"></li>');

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
                return;
            }

            this.group = $(this.options.groupContainer);

            this.main = this._mainButtons($elems);
            if (this.options.useMainButtonsClone) {
                this.main = this._prepareMainButton(this.main);
            }

            this.group.append(this.main);

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
        _dropdownMenu: function($buttons) {
            return $('<ul class="dropdown-menu"></ul>')
                .append(this._prepareButtons($buttons));
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
            return $buttons.filter('.btn')
                .removeClass(function(index, css) {
                    return (css.match(/\bbtn(-\S+)?/g) || []).join(' ');
                }).wrap('<li></li>').parent();
        }
    });

    return $;
});
