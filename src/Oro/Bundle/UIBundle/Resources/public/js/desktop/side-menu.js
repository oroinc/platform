define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('../side-menu');
    const mediator = require('../mediator');
    const persistentStorage = require('oroui/js/persistent-storage');
    const SideMenuOverlay = require('oroui/js/desktop/side-menu-overlay');
    const ScrollingOverlay = require('oroui/js/app/views/scrolling-overlay-view');

    const STATE_STORAGE_KEY = 'main-menu-state';
    const MAXIMIZED_STATE = 'maximized';
    const MINIMIZED_STATE = 'minimized';

    $.widget('oroui.desktopSideMenu', $.oroui.sideMenu, {
        options: {
            menuSelector: '#main-menu',
            innerMenuSelector: '.menu:eq(0)',
            innerMenuItemClassName: 'menu-item'
        },

        overlay: null,

        dropdownIndex: null,

        timeout: 50,

        $currentItem: null,

        /**
         * Do initial changes
         */
        _init: function() {
            this._update();
        },

        /**
         * @inheritdoc
         */
        _create: function() {
            this._super();

            this.$menu = this.element.find(this.options.menuSelector);

            mediator.on('mainMenuUpdated', this.onMenuUpdate, this);

            this._on(this.element, {
                'click .dropdown-level-1': this.onMenuOpen,
                'transitionend .accordion': function() {
                    mediator.trigger('layout:reposition');
                },
                'mainMenuUpdated'() {
                    this.scrollingOverlay.setScrollingContent(this.element.find('.nav-multilevel'));
                }
            });

            this.scrollingOverlay = new ScrollingOverlay({
                autoRender: true,
                scrollStep: this.getHeightOfFirstMenuItem(),
                $scrollingContent: this.element.find('.nav-multilevel')
            });
            this.overlay = new SideMenuOverlay();
            this.overlay
                .on('open', this._attachHandlersFormDocument.bind(this))
                .on('close', this._removeHandlersFormDocument.bind(this));
            this.overlay.render();
            this.$menu.after(this.overlay.$el);
        },

        /**
         * Updates menu's minimized/maximized view
         */
        _update: function() {
            const isMinimized = this.isMinimized();

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
            this.scrollingOverlay.setScrollStep(this.getHeightOfFirstMenuItem());
        },

        getHeightOfFirstMenuItem: function() {
            return Math.ceil(this.element.find('.nav-multilevel').children().first().outerHeight());
        },

        /**
         * Destroys widget's references
         *
         * @private
         */
        _destroy: function() {
            this._super();

            mediator.off('mainMenuUpdated', this.onMenuUpdate, this);
            this.overlay.off();
            this.overlay.dispose();
            delete this.overlay;
            delete this.dropdownIndex;
            this.scrollingOverlay.dispose();
            delete this.scrollingOverlay;

            this._removeHandlersFormDocument();
        },

        /**
         * Attach event handlers for document
         * @private
         */
        _attachHandlersFormDocument: function() {
            let actionInMenu = true;
            const menuContainer = this.$menu.parent()[0];

            $(document)
                .on('click' + this.eventNamespace + ' keydown' + this.eventNamespace, _.debounce(function(e) {
                    // event was fired on scope of menu or not
                    actionInMenu = $.contains(menuContainer, e.target);
                }, this.timeout))
                .on('keyup' + this.eventNamespace, _.debounce(function() {
                    if (actionInMenu && !$.contains(menuContainer, document.activeElement)) {
                        this.overlay.trigger('leave-focus');
                    }
                }.bind(this), this.timeout));
        },

        /**
         * Remove all event handlers for document
         * @private
         */
        _removeHandlersFormDocument: function() {
            this.highlightDropdown(this.$currentItem);
            $(document).off(this.eventNamespace);
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

            const index = $(event.currentTarget).index();

            this.highlightDropdown($(event.currentTarget));

            if (this.dropdownIndex === index && this.overlay.isOpen) {
                this.overlay.close();
            } else {
                const $menu = $(event.currentTarget).find(this.options.innerMenuSelector);

                if (!$menu.length) {
                    return;
                }

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
         * Handles menu update event
         *
         * @param {Object} menuView
         */
        onMenuUpdate: function(menuView) {
            const data = menuView.getTemplateData();
            const currentRoute = menuView.getCurrentRoute(data);
            const item = menuView.getMatchedMenuItem(currentRoute);

            if (!_.isUndefined(item)) {
                this.$currentItem = item.closest('.dropdown-level-1');
            }
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
            if ($element && $element.length) {
                $element
                    .addClass('active')
                    .siblings()
                    .removeClass('active');
            } else {
                this.element
                    .find('.dropdown-level-1.active')
                    .removeClass('active');
            }
        },

        /**
         * Create menu with flat structure
         *
         * @param {Element} $menu
         * @returns {*|jQuery}
         */
        convertToFlatStructure: function($menu) {
            const self = this;
            const collection = [];

            const createFlatStructure = function($menu, parentIndex, parentGroupIndex) {
                const $items = $menu.children();

                $items.each(function(index, menuItem) {
                    const $menuItem = $(menuItem).clone(true, true);
                    let $nestedMenuItem = null;
                    let uniqueIndex = null;
                    let uniqueGroupIndex = null;

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

                    if (parentIndex) {
                        uniqueGroupIndex = (parentGroupIndex ? parentGroupIndex : parentIndex) + ';' + uniqueIndex;
                        $menuItem.attr('data-related-groups', uniqueGroupIndex);
                    }

                    $menuItem
                        .attr('data-index', uniqueIndex)
                        .attr('data-original-text', _.escape($menuItem.text()))
                        .addClass(self.options.innerMenuItemClassName);
                    collection.push($menuItem[0]);

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
