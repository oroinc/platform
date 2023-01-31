import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';

import 'jquery-ui/scroll-parent';

const KEY_CODES = {
    ENTER: 13,
    ESC: 27,
    SPACE: 32,
    END: 35,
    HOME: 36,
    LEFT: 37,
    RIGHT: 39,
    UP: 38,
    DOWN: 40
};

const MENU_BAR_ATTR = 'data-menu-bar';
const MENU_ITEM_INDEX_ATTR = 'data-relative-index';

const NavigationMenuView = BaseView.extend({
    /**
     * @inheritdoc
     */
    events() {
        const events = {
            'keydown': 'onKeyDown',
            [`focus ${this.options.focusableElements}`]: 'onFocus',
            [`focusin ${this.options.focusableElements}`]: 'onFocusInToFocusable',
            [`focusout ${this.options.focusableElements}`]: 'onFocusOutToFocusable',
            'focusout': 'onFocusOut',
            'show.bs.dropdown': 'onDropdownToggle',
            'hide.bs.dropdown': 'onDropdownToggle'
        };

        if (this.options.listenToMouseEvents) {
            events[`mousemove ${this.options.itemSelector}`] = 'onMouseMove';
            events[`mouseleave ${this.options.itemSelector}`] = 'onMouseLeave';
        }

        return events;
    },

    hasFocus: false,

    isPlainMenu: false,

    plainMenuClass: 'menu-is-plain',

    /**
     * @inheritdoc
     */
    constructor: function NavigationMenuView(options) {
        this.onMouseLeave = _.throttle(this.onMouseLeave, 100);

        NavigationMenuView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     * @param {Object} options
     */
    options: {
        openClass: 'show',
        // Elements that may receive a focus during keyboard navigation
        focusableElements: 'a:visible:not([data-ignore-navigation]), button:visible:not([data-ignore-navigation])',
        // Elements that are keyboard focusable but not part of the Tab sequence of the page.
        tabbableElements: 'a:visible, button:visible',
        itemSelector: 'li, [role="listitem"]',
        linkSelector: 'a:first',
        subMenus: 'ul, ol, nav, [data-role="sub-menu"]',
        popupMenuCriteria: '[aria-hidden]',
        closeMenu: '[data-role="close"]',
        listenToMouseEvents: true
    },

    $lastFocusedElementInRow: null,

    preinitialize(options) {
        this.options = {...this.options, ...options};
        this._keysMap = {};
    },

    /**
     * @inheritdoc
     * @param {Object} options
     */
    initialize(options) {
        this.openNextRootMenu = false;

        this.markMenuBar();
        this.markPlainMenu();
        this.setRovingTabIndex();

        this.registerKey(KEY_CODES.ESC, this.onPressedEsc);
        this.registerKey(KEY_CODES.SPACE, this.onPressedSpace);
        this.registerKey(KEY_CODES.END, this.onPressedEnd);
        this.registerKey(KEY_CODES.HOME, this.onPressedHome);

        if (this.isPlainMenu) {
            this.registerKey(KEY_CODES.LEFT, this.onPressedStartSide);
            this.registerKey(KEY_CODES.RIGHT, this.onPressedEndSide);
            // For simple (plain) menu buttons TOP and BOTTOM are doing the some behaviour as LEFT and RIGHT
            this.registerKey(KEY_CODES.UP, this.onPressedStartSide);
            this.registerKey(KEY_CODES.DOWN, this.onPressedEndSide);
        } else {
            if (_.isRTL()) {
                this.registerKey(KEY_CODES.RIGHT, this.onPressedStartSide);
                this.registerKey(KEY_CODES.LEFT, this.onPressedEndSide);
            } else {
                this.registerKey(KEY_CODES.LEFT, this.onPressedStartSide);
                this.registerKey(KEY_CODES.RIGHT, this.onPressedEndSide);
            }

            this.registerKey(KEY_CODES.UP, this.onPressedUp);
            this.registerKey(KEY_CODES.DOWN, this.onPressedDown);
        }

        NavigationMenuView.__super__.initialize.call(this, options);
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        delete this._keysMap;
        delete this._searchData;
        delete this.$lastFocusedElementInRow;
        this.$(this.options.tabbableElements).removeAttr('tabindex');
        this.$el
            .removeAttr(MENU_BAR_ATTR)
            .removeClass(this.plainMenuClass);
        this.$el.children(this.options.subMenus).removeAttr(MENU_BAR_ATTR);

        NavigationMenuView.__super__.dispose.call(this);
    },

    /**
     * @param {number} keyCode
     * @param {function} callback
     */
    registerKey(keyCode, callback) {
        if ($.isNumeric(keyCode) && typeof callback === 'function') {
            this._keysMap[keyCode] = callback;
        }
    },

    /**
     * Add specific attribute to first level menu
     */
    markMenuBar() {
        const $firstLevelMenu = this.$el
            .find(this.options.focusableElements).first().closest(this.options.subMenus, this.$el);
        const $siblings = $firstLevelMenu.siblings(this.options.subMenus);

        if (!$firstLevelMenu.is(this.$el) && $siblings.length) {
            $siblings.attr(MENU_BAR_ATTR, '');
        }

        $firstLevelMenu.attr(MENU_BAR_ATTR, '');
    },

    /**
     * Add specific class when menu does not has any popup menus
     */
    markPlainMenu() {
        this.isPlainMenu = this.$(this.options.subMenus).filter((index, el) => this.isPopupMenu($(el))).length === 0;

        if (this.isPlainMenu) {
            this.$el.addClass(this.plainMenuClass);
        }
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {boolean}
     */
    isMenuBar($menu) {
        if (!$menu && !$menu.length) {
            return false;
        }

        return $menu.attr(MENU_BAR_ATTR) !== void 0;
    },

    /**
     * @param {Object} event
     */
    onKeyDown(event) {
        if ($(event.target).is(this.options.focusableElements)) {
            this.navigateTo(event);
        }
    },

    /**
     * @param {Object} event
     */
    onFocusInToFocusable(event) {
        this.toggleFocusableClass(event.target, true);
    },

    /**
     * @param {Object} event
     */
    onFocusOutToFocusable(event) {
        this.toggleFocusableClass(event.target, false);
    },

    /**
     * @param {Object} event
     */
    onFocus(event) {
        const $element = $(event.target);
        const $currentMenu = this.getCurrentMenu($element);

        this.hasFocus = true;
        this.overloadDataForSearch($element);

        if (!this.isMenuBar($currentMenu)) {
            return;
        }

        this.hideSubMenu();

        if (this.openNextRootMenu && this.isPopupMenu(this.getSubMenu($element))) {
            this.showSubMenu($element);
        }
    },

    /**
     * @param {Object} event
     */
    onFocusOut(event) {
        if (!$.contains(event.currentTarget, event.relatedTarget)) {
            this.openNextRootMenu = false;
            this.hasFocus = false;
            this.setRovingTabIndex(this.getRootFocusableElement($(event.target)));
            this.hideSubMenu();
        }
    },

    /**
     * @param {jQuery.Element} [$element]
     */
    setRovingTabIndex($element) {
        const $tabbableElements = this.$(this.options.tabbableElements);

        if (!$tabbableElements.length) {
            return;
        }

        if (!$element || !$element.length) {
            $element = $tabbableElements.first();
        }

        $tabbableElements.attr('tabindex', -1);
        $element.attr('tabindex', 0);
    },

    /**
     *
     * @param {Object} event
     */
    navigateTo(event) {
        if (
            $(event.target).is(this.options.closeMenu) &&
            (event.keyCode === KEY_CODES.SPACE || event.keyCode === KEY_CODES.ENTER)
        ) {
            this.closeMenu(event);
        } else if (typeof this._keysMap[event.keyCode] === 'function') {
            this._keysMap[event.keyCode].call(this, event);
        } else if (this.isPrintableCharacter(event.key)) {
            this.setFocusByFirstCharacter(event);
        }
    },

    /**
     * @param {string} char
     * @returns {boolean|*}
     */
    isPrintableCharacter(char) {
        return char.length === 1 && char.match(/\S/) !== null;
    },

    /**
     * @param {Object} event
     */
    onPressedEsc(event) {
        this.openNextRootMenu = false;
        this.setFocus(this.getRootFocusableElement($(event.target)));
        this.hideSubMenu();
    },

    /**
     * @param {Object} event
     */
    onPressedSpace(event) {
        const $element = $(event.target);
        const $subMenu = this.getSubMenu($element);

        if (!this.isPopupMenu($subMenu)) {
            return;
        }

        event.preventDefault();

        this.showSubMenu($element);
        this.setFocus(this.getFirstFocusableElement($subMenu));
    },

    /**
     * @param {Object} event
     */
    onPressedEnd(event) {
        event.preventDefault();

        const $currentMenu = this.getCurrentMenu($(event.target));

        this.setFocus(this.getLastFocusableElementInGroup($currentMenu));
    },

    /**
     * @param {Object} event
     */
    onPressedHome(event) {
        event.preventDefault();

        const $currentMenu = this.getCurrentMenu($(event.target));

        this.setFocus(this.getFirstFocusableElementInGroup($currentMenu));
    },

    /**
     * Handler of left or right  buttons depend on the direction of the text of the document
     *
     * @param {Object} event
     */
    onPressedStartSide(event) {
        const $element = $(event.target);
        const $currentMenu = this.getCurrentMenu($element);

        event.preventDefault();

        if (this.isMenuBar($currentMenu)) {
            this.hideSubMenu();
            this.moveFocusToPreviousRelativeSibling($currentMenu);
        } else {
            const $lastOpenedPopup = this.getPopupMenus($element).first();
            const $prevElement = this.getFocusableElementByIndex($lastOpenedPopup.attr(MENU_ITEM_INDEX_ATTR));
            const $prevMenu = this.getCurrentMenu($prevElement);

            this.hideSubMenu($lastOpenedPopup.add($prevElement));
            this.setFocus($prevElement);

            if (this.isMenuBar($prevMenu)) {
                this.moveFocusToPreviousRelativeSibling($prevMenu);
            }
        }
    },

    /**
     * Handler of right or left buttons depend on the direction of the text of the document
     *
     * @param {Object} event
     */
    onPressedEndSide(event) {
        const $element = $(event.target);
        const $currentMenu = this.getCurrentMenu($element);
        const $subMenu = this.getSubMenu($element);

        event.preventDefault();

        if (this.isMenuBar($currentMenu)) {
            this.hideSubMenu();
            this.moveFocusToNextRelativeSibling($currentMenu);
        } else {
            if (this.isPopupMenu($subMenu)) {
                this.showSubMenu($element);
                this.setFocus(this.getFirstFocusableElement($subMenu));
            } else {
                this.setFocus(this.getRootFocusableElement($element));
                this.moveFocusToNextRelativeSibling($element.closest(`[${MENU_BAR_ATTR}]`));
            }
        }
    },

    /**
     * @param {Object} event
     */
    onPressedUp(event) {
        const $element = $(event.target);
        const $currentMenu = this.getCurrentMenu($element);
        const $subMenu = this.getSubMenu($element);

        event.preventDefault();

        if (this.isMenuBar($currentMenu)) {
            if (!this.isPopupMenu($subMenu)) {
                return;
            }

            this.showSubMenu($element);

            let $el;
            const hasPopupMenus = $subMenu.find(this.options.subMenus)
                .filter((index, el) => this.isPopupMenu($(el))).length > 0;

            if (hasPopupMenus) {
                const $directMenu = $subMenu.find(this.options.subMenus).first();

                $el = this.getLastDirectFocusableElement($directMenu.length ? $directMenu : $subMenu);
            } else {
                $el = this.getLastFocusableElement($subMenu);
            }

            this.setFocus($el);
        } else {
            const $prevNext = this.moveFocusToPreviousRelativeSibling($currentMenu);

            this.scrollToEl($prevNext);
        }
    },

    /**
     * @param {Object} event
     */
    onPressedDown(event) {
        const $element = $(event.target);
        const $currentMenu = this.getCurrentMenu($element);
        const $subMenu = this.getSubMenu($element);

        event.preventDefault();

        if (this.isMenuBar($currentMenu)) {
            if (!this.isPopupMenu($subMenu)) {
                return;
            }

            this.hideSubMenu();
            this.showSubMenu($element);
            this.setFocus(this.getFirstFocusableElement($subMenu));
        } else {
            const $nextEl = this.moveFocusToNextRelativeSibling($currentMenu);

            this.scrollToEl($nextEl);
        }
    },

    /**
     * @param {jQuery.Element} $element
     * @returns {jQuery.Element}
     */
    getSubMenu($element) {
        const $menu = $(`#${$element.attr('aria-controls')}`);

        if ($menu.length === 0) {
            return $element.nextAll(this.options.subMenus).first();
        }

        return $menu;
    },

    getMenuBarSubMenu: function(menuLink) {
        const $menuLink = $(menuLink);
        const $currentMenu = this.getCurrentMenu($menuLink);

        if (!this.isMenuBar($currentMenu)) {
            return null;
        }

        const $subMenu = this.getSubMenu($menuLink);

        return this.isPopupMenu($subMenu) ? $subMenu : null;
    },

    /**
     * @param {jQuery.Element} $element
     * @returns {jQuery.Element}
     */
    getCurrentMenu($element) {
        const $currentMenu = $element.closest(this.options.subMenus);

        return $currentMenu.length ? $currentMenu : this.$el;
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {jQuery.Element}
     */
    getDirectFocusableElements($menu) {
        return $menu.find(this.options.focusableElements)
            .filter((index, el) => $(el).closest(this.options.subMenus).is($menu));
    },

    /**
     * @param {jQuery.Element} $popupMenu
     * @returns {jQuery.Element}
     */
    getPopupFocusableElements($popupMenu) {
        return this.$(this.options.focusableElements)
            .filter((i, el) => this.getPopupMenus($(el)).first().is($popupMenu));
    },

    /**
     *  Finding next or previous focusable element from current
     * @param $menu
     * @param {boolean} [isNext=true]
     * @returns {jQuery.Element}
     */
    getClosestFocusableElement($menu, isNext = true) {
        const $focusableItems = $menu.find(this.options.focusableElements)
            .filter((index, el) => {
                const $parentMenu = $(el).closest(this.options.subMenus);
                return $parentMenu.is($menu);
            });
        const index = $focusableItems.index($focusableItems.filter('[tabindex="0"]'));

        if (isNext) {
            return $focusableItems.eq(index + 1);
        }

        return index - 1 < 0 ? $([]) : $focusableItems.eq(index - 1);
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {jQuery.Element}
     */
    getFirstFocusableElement($menu) {
        return $menu.find(this.options.focusableElements).first();
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {jQuery.Element}
     */
    getFirstFocusableElementInGroup($menu) {
        if (this.isMenuBar($menu)) {
            const $siblings = $menu.siblings(this.options.subMenus);

            if ($siblings.length) {
                $menu = $siblings.add($menu).first();
            }

            return this.getFirstFocusableElement($menu);
        }

        $menu = this.isPopupMenu($menu) ? $menu : this.getPopupMenus($menu).first();

        return this.getPopupFocusableElements($menu).first();
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {jQuery.Element}
     */
    getLastFocusableElement($menu) {
        return $menu.find(this.options.focusableElements).last();
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {jQuery.Element}
     */
    getLastDirectFocusableElement($menu) {
        return this.getDirectFocusableElements($menu).last();
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {jQuery.Element}
     */
    getLastFocusableElementInGroup($menu) {
        if (this.isMenuBar($menu)) {
            const $siblings = $menu.siblings(this.options.subMenus);

            if ($siblings.length) {
                $menu = $siblings.add($menu).last();
            }

            return this.getLastDirectFocusableElement($menu);
        }

        $menu = this.isPopupMenu($menu) ? $menu : this.getPopupMenus($menu).first();

        return this.getPopupFocusableElements($menu).last();
    },

    /** Get collection with opened popups menus
     * @param {jQuery.Element} $element
     * @returns {jQuery.Element}
     */
    getPopupMenus($element) {
        return $element.parents(this.options.subMenus, this.$el)
            .filter((index, el) => this.isPopupMenu($(el)));
    },

    /**
     * @param {jQuery.Element} $element
     * @returns {jQuery.Element}
     */
    getRootFocusableElement($element) {
        const index = this.getPopupMenus($element).last().attr(MENU_ITEM_INDEX_ATTR);

        return index === void 0 ? $element : this.getFocusableElementByIndex(index);
    },

    /**
     * @param {number} index
     * @param {jQuery.Element} [$collection]
     * @returns {jQuery.Element}
     */
    getFocusableElementByIndex(index, $collection) {
        let $elements = this.$(this.options.focusableElements);

        if ($collection && $collection.length) {
            $elements = $collection;
        }
        return $elements.eq(index);
    },

    /**
     * @param {jQuery.Element} $focusableElement
     * @param {jQuery.Element|undefined} [$collection]
     * @returns {number}
     */
    getIndexForElement($focusableElement, $collection) {
        let $elements = this.$(this.options.focusableElements);

        if ($collection && $collection.length) {
            $elements = $collection;
        }

        return $elements.index($focusableElement);
    },

    /**
     * @param {jQuery.Element} $menu
     * @returns {boolean}
     */
    isPopupMenu($menu) {
        if (!$menu && !$menu.length) {
            return false;
        }

        if (typeof this.options.popupMenuCriteria === 'function') {
            return $menu.filter(this.options.popupMenuCriteria).length > 0;
        }

        return $menu.is(this.options.popupMenuCriteria);
    },

    /**
     * @param {jQuery.Element} $element
     * @param {boolean} [showNextMenu=true] - show next root menu automatically
     */
    showSubMenu($element, showNextMenu = true) {
        this.openNextRootMenu = showNextMenu;

        if ($element && $element.data('toggle') === 'dropdown') {
            $element.dropdown('show');
            this.$el.trigger('sub-menus:shown');
            return;
        }

        const $menu = this.getSubMenu($element);

        $element
            .attr('aria-expanded', true)
            .parents(this.options.itemSelector).first()
            .addClass(this.options.openClass);
        $menu
            .attr({
                'aria-hidden': false,
                [MENU_ITEM_INDEX_ATTR]: this.getIndexForElement($element)
            })
            .addClass(this.options.openClass);
        this.$el.trigger('sub-menus:shown');
    },

    /**
     * @param {jQuery.Element} [$collection]
     */
    hideSubMenu($collection) {
        if (!$collection) {
            $collection = this.$el.find(this.options.focusableElements + ', ' + this.options.subMenus);
        }

        $collection.each((index, el) => {
            const $el = $(el);

            if ($el.data('toggle') === 'dropdown') {
                $el.dropdown('hide');
            } else if (
                $el.attr('aria-expanded') !== void 0
            ) {
                $el
                    .attr('aria-expanded', false)
                    .parents(this.options.itemSelector).first()
                    .removeClass(this.options.openClass);
            } else if (
                $el.hasClass(this.options.openClass) &&
                $el.is(this.options.subMenus)
            ) {
                $el
                    .attr('aria-hidden', true)
                    .removeAttr(MENU_ITEM_INDEX_ATTR)
                    .removeClass(this.options.openClass);
            }
        });
        this.$el.trigger('sub-menus:hidden');
    },

    /**
     * Circular clockwise traveling around focusable elements in popup menu
     * @param $menu
     * @returns {jQuery.Element}
     */
    moveFocusToNextRelativeSibling($menu) {
        let $el = this.getClosestFocusableElement($menu, true);
        let $parentMenu = $menu.parent().closest(this.options.subMenus);
        const $nextMenu = $menu.next(this.options.subMenus);
        let $nextParentMenu = $parentMenu.next(this.options.subMenus);

        while (!$el.length) {
            if ($nextMenu.length) {
                $el = this.getFirstFocusableElement($nextMenu);
            } else if ($nextParentMenu.length) {
                $el = this.getFirstFocusableElement($nextParentMenu);
            } else {
                if ($parentMenu.length) {
                    if (!this.isPopupMenu($parentMenu)) {
                        $parentMenu = $parentMenu.parent().closest(this.options.subMenus);
                        $nextParentMenu = $parentMenu.next(this.options.subMenus);
                        continue;
                    }

                    const hasPopupSubMenu = $parentMenu.find(this.options.subMenus)
                        .filter((index, el) => this.isPopupMenu($(el))).length > 0;

                    if (!hasPopupSubMenu) {
                        $el = this.getFirstFocusableElement($parentMenu);
                    }
                }

                if (!$el.length) {
                    const $siblingMenu = $menu.siblings(this.options.subMenus);

                    $el = this.getFirstFocusableElement($siblingMenu.length
                        ? $siblingMenu.add($menu).first() : $menu);
                }
            }
        }

        this.setFocus($el);
        return $el;
    },

    /**
     * Circular counterclockwise traveling around focusable elements in popup menu
     * @param $menu
     * @returns {jQuery.Element}
     */
    moveFocusToPreviousRelativeSibling($menu) {
        let $el = this.getClosestFocusableElement($menu, false);
        let $parentMenu = $menu.parent().closest(this.options.subMenus);
        const $prevMenu = $menu.prev(this.options.subMenus);
        let $prevParentMenu = $parentMenu.prev(this.options.subMenus);

        while (!$el.length) {
            if ($prevMenu.length) {
                $el = this.getLastFocusableElement($prevMenu);
            } else if ($prevParentMenu.length) {
                $el = this.getLastFocusableElement($prevParentMenu);
            } else {
                if ($parentMenu.length) {
                    if (!this.isPopupMenu($parentMenu)) {
                        $parentMenu = $parentMenu.parent().closest(this.options.subMenus);
                        $prevParentMenu = $parentMenu.prev(this.options.subMenus);
                        continue;
                    }

                    const hasPopupSubMenu = $parentMenu.find(this.options.subMenus)
                        .filter((index, el) => this.isPopupMenu($(el))).length > 0;

                    if (!hasPopupSubMenu) {
                        $el = this.getLastFocusableElement($parentMenu);
                    }
                }

                if (!$el.length) {
                    const $siblingMenu = $menu.siblings(this.options.subMenus);

                    $el = this.getLastDirectFocusableElement($siblingMenu.length
                        ? $siblingMenu.add($menu).last() : $menu);
                }
            }
        }

        this.setFocus($el);
        return $el;
    },

    /**
     * @param {jQuery.Element} $el
     */
    setFocus($el) {
        if (!$el && !$el.length) {
            return;
        }

        $el.trigger('focus');
        this.setRovingTabIndex($el);
    },

    /**
     * @param {Object} event
     */
    setFocusByFirstCharacter(event) {
        if (!this._searchData) {
            return;
        }

        const char = event.key.toLowerCase();
        let index = -1;
        let start = this.getIndexForElement($(event.target), this._searchData.$elements) + 1;
        const findIndexByFirstCharts = (firstChars, char, start, end) => {
            for (let i = start; i < end; i++) {
                if (char === firstChars[i]) {
                    return i;
                }
            }
            return -1;
        };

        if (start === this._searchData.$elements.length) {
            start = 0;
        }

        index = findIndexByFirstCharts(this._searchData.firstChars, char, start, this._searchData.firstChars.length);

        // Not found check from beginning
        if (index === -1) {
            index = findIndexByFirstCharts(this._searchData.firstChars, char, 0, start);
        }

        // Matches were found
        if (index > -1) {
            this.setFocus(this.getFocusableElementByIndex(index, this._searchData.$elements));
        }
    },

    /**
     * @param {Object} event
     */
    closeMenu(event) {
        event.preventDefault();

        this.openNextRootMenu = false;
        this.setFocus(this.getRootFocusableElement($(event.target)));
        this.hideSubMenu();
        this.$el.trigger('close-menus', this.$el);
    },

    /**
     * @param {jQuery.Element} $element
     */
    scrollToEl($element) {
        const $scrollParent = $element.scrollParent();
        const scrollBottom = $scrollParent.offset().top + $scrollParent.outerHeight(true);
        const elementTop = $element.offset().top;

        if (
            this.el.contains($scrollParent[0]) &&
            elementTop <= scrollBottom
        ) {
            return;
        }

        $scrollParent.scrollTop(elementTop + $element.outerHeight(true) - scrollBottom);
    },

    /**
     * @param {jQuery.Element} $elements
     */
    prepareDataForSearch($elements) {
        if ($elements && !$elements.length) {
            return;
        }

        const firstChars = $elements.map(
            (index, el) => $(el).text().trim().substring(0, 1).toLowerCase()
        );

        this._searchData = {$elements, firstChars};
    },

    /**
     * @params (jQuery.Element) $element
     */
    overloadDataForSearch($element) {
        if ($element && !$element.length) {
            return;
        }

        const $popupMenu = this.getPopupMenus($element).first();
        let $rowFocusableElements;

        if ($popupMenu.length) {
            $rowFocusableElements = this.getPopupFocusableElements($popupMenu);
        } else {
            const $rootMenu = this.$el.attr(MENU_BAR_ATTR) !== void 0 ? this.$el : this.$(`[${MENU_BAR_ATTR}]`);

            $rowFocusableElements = $([]);
            $rootMenu.each((index, el) => {
                $rowFocusableElements = $rowFocusableElements.add(this.getDirectFocusableElements($(el)));
            });
        }

        if (!this.$lastFocusedElementInRow) {
            this.$lastFocusedElementInRow = $element;
            this.prepareDataForSearch($rowFocusableElements);
        } else if ($rowFocusableElements.index(this.$lastFocusedElementInRow) === -1) {
            this.$lastFocusedElementInRow = $element;
            this.prepareDataForSearch($rowFocusableElements);
        }
    },

    /**
     * Handler on bootstrap dropdown toggle
     * @param {Object} event
     */
    onDropdownToggle(event) {
        const $menu = this.getSubMenu($(event.relatedTarget));

        $menu.attr('aria-hidden', event.type === 'hide');

        if (event.type === 'show') {
            $menu.attr(MENU_ITEM_INDEX_ATTR, this.getIndexForElement($(event.relatedTarget)));
        } else if (event.type === 'hide') {
            $menu.removeAttr(MENU_ITEM_INDEX_ATTR);
        }
    },

    /**
     * @param {Object} e
     */
    onMouseMove: function(e) {
        const $menuLink = $(e.currentTarget).find(this.options.linkSelector);
        const $subMenu = this.getMenuBarSubMenu($menuLink);

        if ($subMenu && !$subMenu.is(':visible')) {
            if (this.hasFocus) {
                this.setFocus(this.getRootFocusableElement($(document.activeElement)));
            }

            this.hideSubMenu();
            this.showSubMenu($menuLink);
        }
    },

    /**
     * @param {Object} e
     */
    onMouseLeave: function(e) {
        if (!this.hasFocus) {
            this.hideSubMenu();
        } else {
            const $menuLink = $(e.currentTarget).find(this.options.linkSelector);
            const $subMenu = this.getMenuBarSubMenu($menuLink);

            if ($subMenu && $subMenu.is(':visible') && !$.contains($subMenu[0], document.activeElement)) {
                this.hideSubMenu();
            }
        }
    },

    /**
     * @param {HTMLElement} el
     * @param {boolean} state
     */
    toggleFocusableClass(el, state) {
        $(el).toggleClass('focus-via-arrows-keys', state);
    }
});

export default NavigationMenuView;
