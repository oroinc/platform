define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('../side-menu');
    var mediator = require('../mediator');
    var persistentStorage = require('oroui/js/persistent-storage');
    var SideMenuOverlay = require('oroui/js/desktop/side-menu-overlay');

    var STATE_STORAGE_KEY = 'main-menu-state';
    var MAXIMIZED_STATE = 'maximized';
    var MINIMIZED_STATE = 'minimized';
    var BROWSER_SCROLL_SIZE = mediator.execute('layout:scrollbarWidth');

    $.widget('oroui.desktopSideMenu', $.oroui.sideMenu, {
        options: {
            menuSelector: '#main-menu',
            dropdownSelector: '.dropdown-level-1',
            innerMenuSelector: '.menu:eq(0)',
            innerMenItemClassName: 'menu-item'
        },

        overlay: null,

        dropdownIndex: null,

        /**
         * Do initial changes
         */
        _init: function() {
            this._update();
        },

        /**
         * @inheritDoc
         */
        _create: function() {
            this._super();

            this.listener.listenTo(mediator, 'layout:reposition', _.debounce(this.onChangeReposition, 50).bind(this));

            this.$mainiMenu = this.element.find(this.options.menuSelector);
            this.$triggers = this.element.find(this.options.dropdownSelector);

            this._on(this.$triggers, {click: this.onMenuOpen});

            this.overlay = new SideMenuOverlay();
            this.overlay.render();
            this.$mainiMenu.append(this.overlay.$el);
        },

        /**
         * Updates menu's minimized/maximized view
         */
        _update: function() {
            var isMinimized = this.isMinimized();

            this.element.toggleClass('minimized', isMinimized);

            this.overlay.close();

            if (isMinimized) {
                this._convertToDropdown();
            } else {
                this._convertToAccordion();
            }
        },

        /**
         * Handles menu toggle state action
         */
        _toggle: function() {
            persistentStorage.setItem(
                STATE_STORAGE_KEY,
                this.element.hasClass('minimized') ? MAXIMIZED_STATE : MINIMIZED_STATE
            );
            this._update();
            mediator.trigger('layout:adjustHeight');
        },

        /**
         * Destroys widget's references
         *
         * @private
         */
        _destroy: function() {
            this._super();

            this.overlay.dispose();
            delete this.overlay;
            delete this.dropdownIndex;
        },

        /**
         * Change sidebar width for minimized state
         */
        onChangeReposition: function() {
            this.element.css('width', '');

            if (this.element.hasClass('minimized')) {
                if (this.$mainiMenu[0].offsetWidth > this.$mainiMenu[0].clientWidth) {
                    this.element.css('width', this.element.outerWidth() + BROWSER_SCROLL_SIZE);
                }
            }
        },

        /**
         * Handle menu open action
         *
         * @param {Event} event
         */
        onMenuOpen: function(event) {
            if (!this.isMinimized()) {
                return;
            }

            var index = $(event.currentTarget).index();

            this.highlightDropdown($(event.currentTarget));

            if (this.dropdownIndex === index && this.overlay.isOpen) {
                this.overlay.close();
            } else {
                var $menu = $(event.currentTarget).find(this.options.innerMenuSelector);

                this.overlay
                    .setTitle(
                        $(event.currentTarget)
                            .find('.title-level-1:first')
                            .text()
                    )
                    .updateContent(
                        this.convertToFlatStructure($menu)
                    )
                    .open();
            }

            this.dropdownIndex = index;
        },

        /**
         * @returns {boolean}
         */
        isMinimized: function() {
            return persistentStorage.getItem(STATE_STORAGE_KEY) !== MAXIMIZED_STATE;
        },

        /**
         * @param {Element} $element
         */
        highlightDropdown: function($element) {
            $element
                .addClass('active')
                .siblings()
                .removeClass('active');
        },

        /**
         * Create menu with flat structure
         *
         * @param {Element} $menu
         * @returns {*|jQuery}
         */
        convertToFlatStructure: function($menu) {
            var self = this;
            var collection = [];

            var createFlatStructure = function($menu, parentIndex, parentGroupIndex) {
                var $items = $menu.children();

                $items.each(function(index, menuItem) {
                    var $menuItem = $(menuItem).clone(true, true);
                    var $nestedMenuItem = null;
                    var uniqueIndex = null;
                    var uniqueGroupIndex = null;

                    if (!parentIndex) {
                        uniqueIndex = 'id:' + index;
                    } else {
                        uniqueIndex = parentIndex + '-' + index;
                    }

                    if ($menuItem.hasClass('dropdown')) {
                        $nestedMenuItem = $menuItem.clone(true, true);

                        // Remove inner menu
                        $menuItem.children().last().remove();
                    }

                    if (!$menuItem.hasClass('divider')) {
                        if (parentIndex) {
                            uniqueGroupIndex = (parentGroupIndex ? parentGroupIndex : parentIndex) + ';' + uniqueIndex;
                            $menuItem.attr('data-related-groups', uniqueGroupIndex);
                        }

                        $menuItem
                            .attr('data-index', uniqueIndex)
                            .attr('data-original-text', $menuItem.text())
                            .addClass(self.options.innerMenItemClassName);

                        collection.push($menuItem[0]);
                    }

                    if ($nestedMenuItem) {
                        createFlatStructure(
                            $nestedMenuItem.find(self.options.innerMenuSelector),
                            uniqueIndex,
                            uniqueGroupIndex
                        );
                    }
                }, this);
            };

            createFlatStructure($menu, null, null);

            return $('<ul>', {'class': $menu.attr('class')}).append(collection);
        }
    });

    return 'desktopSideMenu';
});
