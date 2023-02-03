define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const stickyElementMixin = require('oroui/js/app/views/sticky-element/sticky-element-mixin');
    require('jquery-ui/widget');

    /**
     * Converts buttons sequence from container to group with main buttons
     * and rest buttons in dropdown
     */
    $.widget('oroui.dropdownButtonProcessor', _.extend({}, stickyElementMixin, {
        options: {
            separator: '.separator-btn',
            includeButtons: '.btn, .divider, .dropdown-divider, .dropdown-menu>li>*',
            excludeButtons: '.dropdown-toggle',
            mainButtons: '.main-group:not(.more-group)',
            useMainButtonsClone: false,
            truncateLength: null,
            moreLabel: '',
            groupContainer: '<div class="dropdown btn-group"></div>',
            minItemQuantity: 1,
            moreButtonAttrs: {},
            decoreClass: null
        },

        group: null,

        main: null,

        dropdown: null,

        _create: function() {
            this._togglerId = _.uniqueId('dropdown-toggle-');
            // replaces button's separators
            this.element
                .find(this.options.separator)
                .replaceWith('<div class="dropdown-divider" aria-hidden="true"></div>');

            this._renderButtons();
        },

        _destroy: function() {
            this.disposeSticky();

            delete this.group;
            delete this.main;
            delete this.dropdown;
        },

        _renderButtons: function() {
            let $elems = this._collectButtons();
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
                const $moreButton = this._moreButton();
                this.group.append($moreButton);

                this.initializeSticky({
                    $stickyElement: $moreButton,
                    stickyOptions: this.options.stickyOptions
                });

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
            let $main = $buttons.filter(this.options.mainButtons);
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
            const $button = $('<a></a>');
            $button
                .attr($.extend({
                    'id': this._togglerId,
                    'href': '#',
                    'role': 'button',
                    'aria-label': __('oro.ui.dropdown_option_aria_label'),
                    'aria-haspopup': true,
                    'aria-expanded': false,
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
                'class': 'dropdown-menu',
                'role': 'menu',
                'aria-labelledby': this._togglerId
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
                const self = this;
                // set text value string
                $main.contents().each(function() {
                    if (this.nodeType === Node.TEXT_NODE) {
                        const text = this.nodeValue.trim();
                        const shortText = text.substring(0, self.options.truncateLength);
                        if (shortText !== text) {
                            this.parentNode.setAttribute('title', text);
                            this.parentNode
                                .setAttribute('aria-label', __('oro.ui.dropdown_main_btn_prefix') + ' ' + text);
                            this.nodeValue = shortText + '\u2026';
                        }
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
                }).wrap('<li role="menuitem"></li>').parent();
        },

        prepareDropdownButtons: function($buttons) {
            return this._prepareButtons($buttons);
        }
    }));

    return $;
});
