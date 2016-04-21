define(['jquery', 'underscore', 'oroui/js/mediator', 'jquery-ui'], function($, _, mediator) {
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
            moreButtonAttrs: {},
            addButtonEvent: '',
            buttonTemplate: ''
        },

        group: null,

        buttons: null,

        _create: function() {
            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
            }

            if (this.options.addButtonEvent) {
                mediator.on(this.options.addButtonEvent, this._addButton, this);
            }

            // replaces button's separators
            this.element.find(this.options.separator).replaceWith('<li class="divider"></li>');

            this._proccedButtons(this._collectButtons());
        },

        _destroy: function() {
            delete this.group;
            delete this.buttons;
            mediator.off(this.options.addButtonEvent, this._addButton, this);
        },

        _proccedButtons: function($elems) {
            if ($elems.length <= 1) {
                return;
            }
            this.buttons = $elems.clone(true).get();

            if (this.group) {
                this.group.remove();
            }
            this.group = $(this.options.groupContainer);

            var $main = this._mainButtons($elems);
            if (this.options.useMainButtonsClone) {
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
            }
            this.group.append($main);

            // pushes rest buttons to dropdown
            $elems = $elems.not($main);
            if ($elems.length > this.options.minItemQuantity) {
                this.group.append(this._moreButton());
                $elems = this._dropdownMenu($elems);
            }
            this.group.append($elems);

            this.element.find('.btn-group').remove().end().prepend(this.group);
        },

        _addButton: function(data) {
            var $button = this._collectButtons($(this.options.buttonTemplate(data)));
            if ($button.length > 0) {
                var buttons = this.buttons ? this.buttons : this._collectButtons().get();
                buttons.unshift($button.get(0));
                this._proccedButtons($(buttons));
            }
        },

        /**
         * Collects all buttons of the container
         *
         * @param {jQuery|null} $element
         * @returns {*}
         * @private
         */
        _collectButtons: function($element) {
            if (!$element) {
                $element = this.element;
            }
            return $element
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
                .append($buttons)
                .find('.btn')
                .wrap('<li></li>')
                .removeClass(function(index, css) {
                    return (css.match(/\bbtn(-\S+)?/g) || []).join(' ');
                }).end();
        }
    });

    return $;
});
