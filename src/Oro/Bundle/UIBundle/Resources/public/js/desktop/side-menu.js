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
    var ENTER_KEY_CODE = 13;

    $.widget('oroui.desktopSideMenu', $.oroui.sideMenu, {
        options: {
            menuSelector: '#main-menu',
            innerMenuSelector: '.menu:eq(0)',
            innerMenuItemClassName: 'menu-item',
            invisibleClassName: 'invisible'
        },

        overlay: null,

        dropdownIndex: null,

        timeout: 50,

        timer: null,

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

            this.listener
                .listenTo(mediator, 'layout:reposition', _.debounce(this.onChangeReposition, this.timeout).bind(this));

            this.$menu = this.element.find(this.options.menuSelector);
            this.$menuScrollContent = this.element.find('.nav-multilevel');
            this.$scrollHandles = this.element.find('[data-role="scroll-trigger"]');
            this.scrollStep = this.getScrollStep();

            this._on(this.element, {
                'click .dropdown-level-1': this.onMenuOpen,
                'keydown [data-role="scroll-trigger"]': this.onMenuScroll,
                'mousedown [data-role="scroll-trigger"]': this.onMenuHoldScroll,
                'mouseup [data-role="scroll-trigger"]': this.undoMenuHoldScroll,
                'mouseout [data-role="scroll-trigger"]': this.undoMenuHoldScroll,
                'transitionend .accordion': function() {
                    mediator.trigger('layout:reposition');
                }
            });
            this._on(this.$menuScrollContent, {
                scroll: _.debounce(this.toggleScrollTriggers, this.timeout)
            });

            this.overlay = new SideMenuOverlay();
            this.overlay.render();
            this.$menu.after(this.overlay.$el);

            $(document).on('focusout' + this.eventNamespace, _.debounce(function() {
                if (!$.contains(this.$menu.parent()[0], document.activeElement)) {
                    this.overlay.trigger('leave-focus');
                }
            }.bind(this), this.timeout));
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
            this.scrollStep = this.getScrollStep();
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

            $(document).off(this.eventNamespace);
        },

        /**
         * Change sidebar width for minimized state
         */
        onChangeReposition: function() {
            this.toggleScrollTriggers();
        },

        /**
         * Show / hide scroll handles
         */
        toggleScrollTriggers: function() {
            var bottomPosition = _.reduce(this.$menuScrollContent.children(), function(result, item) {
                return result + $(item).outerHeight();
            }, 0);
            var scrollContentHeight = this.$menuScrollContent.outerHeight();
            var scrollTop = this.$menuScrollContent.scrollTop();

            this.$scrollHandles.removeClass(this.options.invisibleClassName);

            if (scrollContentHeight >= bottomPosition) {
                this.$scrollHandles.addClass(this.options.invisibleClassName);
                return;
            }

            this.$scrollHandles
                .filter('[data-direction="up"]')
                .toggleClass(this.options.invisibleClassName,
                    scrollTop === 0
                );

            this.$scrollHandles
                .filter('[data-direction="down"]')
                .toggleClass(
                    this.options.invisibleClassName,
                    scrollTop >= bottomPosition - scrollContentHeight
                );
        },

        /**
         * @returns {number}
         */
        getScrollStep: function() {
            return Math.ceil(this.$menuScrollContent.children().first().outerHeight());
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
         * Handle menu scroll action
         *
         * @param {Event} event
         */
        onMenuScroll: function(event) {
            if (typeof event.keyCode === 'number' && event.keyCode !== ENTER_KEY_CODE) {
                return;
            }

            this.toggleScrollTriggers();

            switch ($(event.currentTarget).data('direction')) {
                case 'up':
                    this.$menuScrollContent.scrollTop(this.$menuScrollContent.scrollTop() - this.scrollStep);
                    break;
                case 'down':
                    this.$menuScrollContent.scrollTop(this.$menuScrollContent.scrollTop() + this.scrollStep);
                    break;
            }
        },

        /**
         * Undo scroll
         */
        undoMenuHoldScroll: function() {
            clearInterval(this.timer);
        },

        /**
         * Handle menu hold scroll action
         *
         * @param {Event} event
         */
        onMenuHoldScroll: function(event) {
            this.onMenuScroll(event);

            this.timer = setInterval(function() {
                this.onMenuScroll(event);
            }.bind(this), 150);
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
