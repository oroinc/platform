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
            decoreClass: null,
            stickyButton: {
                enabled: false,
                btnClass: 'btn-sticky',
                stubClass: 'btn-sticky-stub'
            }
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
            this._disposeStickyButton();

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
                var $moreButton = this._moreButton();
                this.group.append($moreButton);

                if (this.options.stickyButton.enabled) {
                    _.defer(this._stickButton.bind(this, $moreButton));
                }

                $elems = this.dropdown = this._dropdownMenu($elems);
            }
            this.group.append($elems);

            this.element.find('.btn-group').remove().end().prepend(this.group);
        },

        /**
         * Makes button stick to it's current position
         *
         * @param {jQuery} $button
         * @returns {jQuery}
         */
        _stickButton: function($button) {
            var boundaries = this._getBoundaries($button.get(0));

            $('<span></span>')
                .addClass(this.options.stickyButton.stubClass)
                .css(_.extend({
                    display: 'inline-block'
                }, _.pick(boundaries, 'width', 'height')))
                .insertAfter($button);

            this._setStickyPosition = _.bind(this._setStickyPosition, this);

            $button
                .addClass(this.options.stickyButton.btnClass)
                .css(_.extend({
                    position: 'fixed'
                }, _.pick(boundaries, 'top', 'left')));

            $(window).on('resize.sticky', {
                $element: $button
            }, this._setStickyPosition);

            return $button;
        },

        /**
         * Gets coords object of element relative to body
         *
         * @param {element} element
         * @returns {object}
         */
        _getBoundaries: function(element) {
            var elemRect = element.getBoundingClientRect();
            var bodyRect = document.body.getBoundingClientRect();

            return {
                left: elemRect.left - bodyRect.left,
                top: elemRect.top - bodyRect.top,
                width: elemRect.width,
                height: elemRect.height
            };
        },

        /**
         * Sets coords to button from event data
         *
         * @param {event} event
         */
        _setStickyPosition: function(event) {
            var $button = event.data.$element;
            var $stub = $button.next('.' + this.options.stickyButton.stubClass);
            var newCoords = $stub.length && this._getBoundaries($stub.get(0)) || {};

            $button.css(_.pick(newCoords, 'top', 'left'));
        },

        /**
         * Removes stickyButton data
         *
         */
        _disposeStickyButton: function() {
            $(window).off('resize.sticky', this._setStickyPosition);
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
