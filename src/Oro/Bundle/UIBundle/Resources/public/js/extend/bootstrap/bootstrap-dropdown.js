define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    let config = require('module-config').default(module.id);

    const Popper = require('popper').default;
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    const scrollLocker = require('oroui/js/app/services/body-scroll-locker').default;
    const Dropdown = require('bootstrap-dropdown');
    const original = _.clone(Dropdown.prototype);
    const _clearMenus = Dropdown._clearMenus;

    const DATA_KEY = 'bs.dropdown';
    const EVENT_KEY = '.' + DATA_KEY;
    const DATA_API_KEY = '.data-api';
    const SHOWN_EVENT = 'shown' + EVENT_KEY;
    const HIDE_EVENT = 'hide' + EVENT_KEY;
    const TO_HIDE_EVENT = 'tohide' + EVENT_KEY;
    const HIDING_EVENT = 'hiding' + EVENT_KEY;
    const HIDDEN_EVENT = 'hidden' + EVENT_KEY;
    const GRID_SCROLLABLE_CONTAINER = '.grid-scrollable-container';
    const DIALOG_SCROLLABLE_CONTAINER = '.ui-dialog-content';
    const SCROLLABLE_CONTAINER = [
        DIALOG_SCROLLABLE_CONTAINER,
        GRID_SCROLLABLE_CONTAINER
    ].join(',');
    const ESCAPE_KEYCODE = 27; // KeyboardEvent.which value for Escape (Esc) key
    const SPACE_KEYCODE = 32; // KeyboardEvent.which value for space key
    const ARROW_UP_KEYCODE = 38; // KeyboardEvent.which value for up arrow key
    const ARROW_DOWN_KEYCODE = 40; // KeyboardEvent.which value for down arrow key
    const REGEXP_KEYDOWN = new RegExp(`${ARROW_UP_KEYCODE}|${ARROW_DOWN_KEYCODE}|${ESCAPE_KEYCODE}`);
    const ClassName = {
        DISABLED: 'disabled',
        SHOW: 'show',
        DROPUP: 'dropup',
        DROPRIGHT: 'dropright',
        DROPLEFT: 'dropleft',
        MENURIGHT: 'dropdown-menu-right',
        MENULEFT: 'dropdown-menu-left',
        POSITION_STATIC: 'position-static'
    };
    const Selector = {
        DATA_TOGGLE: '[data-toggle="dropdown"]',
        FORM_CHILD: '.click',
        MENU: '.dropdown-menu',
        VISIBLE_ITEMS: '.dropdown-menu .dropdown-item:visible:not(.disabled):not(:disabled)'
    };

    config = _.extend({
        displayArrow: true,
        keepSeparately: true,
        fullscreenable: false
    }, config);

    _.extend(Dropdown.prototype, {
        toggle() {
            Dropdown._togglingElement = this._element;
            Dropdown._isShowing = !$(this._menu).hasClass('show');

            original.toggle.call(this);
            this.syncAriaExpanded();

            // Prevent to show the keyboard on mobile devices if the first element is input
            if (Dropdown._isShowing) {
                this.bindKeepFocusInside();
            } else {
                this.unbindKeepFocusInside();
            }

            if (this._displayArrow()) {
                $(this._menu).attr('x-displayed-arrow', Dropdown._isShowing ? '' : null);
            }

            delete Dropdown._togglingElement;
            delete Dropdown._isShowing;
        },

        show() {
            original.show.call(this);
            this.syncAriaExpanded();
            this.bindKeepFocusInside();
        },

        hide() {
            original.hide.call(this);
            this.syncAriaExpanded();
            this.unbindKeepFocusInside();
        },

        bindKeepFocusInside() {
            $(this._menu).on(_events(['keydown']), e => {
                if (e.keyCode === ESCAPE_KEYCODE) {
                    e.stopPropagation();
                    this.hide();
                    this._element.focus();
                } else {
                    manageFocus.preventTabOutOfContainer(e, e.currentTarget);
                }
            });
        },

        unbindKeepFocusInside() {
            $(this._menu).off(_events(['keydown']));
        },

        syncAriaExpanded() {
            this._element.setAttribute('aria-expanded', $(this._menu).hasClass(ClassName.SHOW));
        },

        dispose() {
            const parent = Dropdown._getParentFromElement(this._element);
            $(parent).off(EVENT_KEY);

            if (this._dialog) {
                $(this._dialog).off(EVENT_KEY);
                delete this._dialog;
            }

            if (this._popper !== null) {
                scrollLocker.removeLocker(this._popper.options.cid);
            }
            original.dispose.call(this);
        },

        _getConfig() {
            const config = original._getConfig.call(this);

            if ('adjustHeight' in config) {
                // empty attribute `data-adjust-height` considered as turn ON option
                config.adjustHeight = config.adjustHeight === '' || config.adjustHeight;
            }

            return config;
        },

        _getMenuElement() {
            original._getMenuElement.call(this);

            if (!this._menu) {
                // if the menu element wasn't found by selector `.dropdown-menu`,
                // the element next to toggler button is considered as menu
                this._menu = $(this._element).next();
            }

            return this._menu;
        },

        _addEventListeners() {
            this._popperUpdate = this._popperUpdate.bind(this);

            original._addEventListeners.call(this);

            const parent = Dropdown._getParentFromElement(this._element);
            const dialogContent = $(this._element).closest(DIALOG_SCROLLABLE_CONTAINER);

            this._dialog = dialogContent.length && dialogContent.parent() || null;

            $(this._element).add(parent).on(TO_HIDE_EVENT, function(event) {
                event.stopImmediatePropagation();
                if ($(this._menu).hasClass('show')) {
                    this.toggle();
                }
            }.bind(this));

            $(parent).on(SHOWN_EVENT, this._onShown.bind(this));
            $(parent).on(HIDE_EVENT, this._onHide.bind(this));
            $(parent).on(HIDDEN_EVENT, this._onHidden.bind(this));

            if (this._dialog) {
                $(this._dialog).on(
                    _events(['dialogresize', 'dialogdrag', 'dialogreposition']),
                    this._popperUpdate
                );
            }
        },

        _popperUpdate(e) {
            if (this._popper) {
                // When scrolling leads to hidden dropdown appears again, single call of scroll handler
                // shows dropdown menu in wrong position. But since single scroll event happens very
                // rarely in real life the next scroll event sets dropdown menu correctly.
                // To emulate similar effect for custom scroll just call `scheduleUpdate` twice
                this._popper.scheduleUpdate();
                this._popper.scheduleUpdate();
            }
        },

        /**
         * Handles 'shown' event
         *
         * @param event
         * @protected
         */
        _onShown(event) {
            let focusTabbable = null;

            if (_.isMobile()) {
                focusTabbable = $(this._menu).find(':tabbable:not("input")').eq(0);
            }

            // If it's an iOS safari need to fix issue with
            // focused field when dropdown is position: fixed;
            if (tools.isIOS()) {
                if (this._delayedFocusTabbable) {
                    clearTimeout(this._delayedFocusTabbable);
                    this._delayedFocusTabbable = null;
                }

                this._delayedFocusTabbable = _.delay(() => {
                    manageFocus.focusTabbable($(this._menu), focusTabbable);
                }, 20);
            } else {
                manageFocus.focusTabbable($(this._menu), focusTabbable);
            }
        },

        /**
         * Handles 'hide' event triggered from _clearMenus
         *
         * @param event
         * @protected
         */
        _onHide(event) {
            if (this._element !== event.relatedTarget) {
                return;
            }

            if (Dropdown._isShowing && $.contains(this._menu, Dropdown._togglingElement)) {
                // prevent parent menu close on opening nested dropdown
                event.preventDefault();
            }

            let $clickTarget;

            if (
                Dropdown._clickEvent &&
                this._config.preventCloseOnMenuClick === true &&
                ($clickTarget = $(Dropdown._clickEvent.target)) &&
                $clickTarget.closest('.dropdown-menu').is(this._menu) &&
                !$clickTarget.is('[data-role="close"]')
            ) {
                // prevent parent menu close on click inside
                event.preventDefault();
            }

            if (!event.isDefaultPrevented()) {
                $(this._menu).trigger(HIDING_EVENT);
            }
        },

        _onHidden(event) {
            // removing popper scroll listeners when dropdown is hidden.
            this._popperDestroy();
            // unassign delayed method
            if (this._delayedFocusTabbable) {
                clearTimeout(this._delayedFocusTabbable);
                this._delayedFocusTabbable = null;
            }
        },

        _popperDestroy() {
            if (this._popper !== null) {
                scrollLocker.removeLocker(this._popper.options.cid);
                // the fix deletes previews instance to prevent memory leaks
                this._popper.destroy();
                this._popper = null;
            }
        },

        _getPopperConfig() {
            const config = original._getPopperConfig.call(this);
            config.cid = _.uniqueId('popper');

            if (!config.positionFixed && $(this._element).closest(SCROLLABLE_CONTAINER).length) {
                // dropdowns are shown with position fixed inside scrollable container, to fix overflow
                config.positionFixed = true;
            }

            if (this._config.inheritParentWidth) {
                const inheritParentWidth = this._config.inheritParentWidth;
                config.positionFixed = true;
                config.modifiers.offset = {
                    fn(data, options) {
                        const popper = data.instance.popper;
                        const offset = data.offsets.popper;

                        if (
                            offset.width &&
                            (inheritParentWidth === 'strictly' || offset.width < popper.parentElement.clientWidth)
                        ) {
                            popper.style.width = popper.parentElement.clientWidth + 'px';
                            _.extend(offset, _.pick(
                                popper.parentElement.getBoundingClientRect(),
                                'left',
                                'right',
                                'width')
                            );
                        }

                        Popper.Defaults.modifiers.offset.fn(data, options);

                        return data;
                    }
                };
            }

            // https://popper.js.org/popper-documentation.html#Popper.Defaults
            _.extend(config, _.pick(this._config, 'placement', 'positionFixed', 'eventsEnabled',
                'onCreate', 'onUpdate', 'onDestroy'));
            _.extend(config.modifiers, _.pick(this._config.modifiers, 'shift', 'offset', 'preventOverflow',
                'keepTogether', 'arrow', 'flip', 'inner', 'hide', 'computeStyle', 'applyStyle'));

            if (this._config.adjustHeight && config.placement.substring(0, 6) === 'bottom') {
                config.modifiers.adjustHeight = {enabled: true};
                config.modifiers.flip = {enabled: false};
            }

            if (this._config.fullscreenable && config.placement.substring(0, 6) === 'bottom') {
                config.modifiers.fullscreenable = {
                    enabled: true,
                    fn: (data, options) => {
                        const menu = this._getMenuElement();
                        let close = $(menu).children('.dropdown-close')[0];

                        if (!close) {
                            close = document.createElement(menu.tagName.toLowerCase() === 'ul' ? 'li' : 'span');
                            close.innerHTML =
                                '<button data-autofocus="false" class="btn btn-icon btn-lighter" type="button">' +
                                '<span class="icon fa-close"></span></button>';
                            close.classList.add('dropdown-close');
                            close.setAttribute('data-helper-element', '');
                            if (menu.hasChildNodes()) {
                                menu.insertBefore(close, menu.firstChild);
                            } else {
                                menu.appendChild(close);
                            }
                        }

                        return Popper.Defaults.modifiers.fullscreenable.fn(data, options);
                    }
                };
                config.modifiers.flip = {enabled: false};

                const {onUpdate = () => {}, onCreate = () => {}} = config;
                const updateScrollLocker = data => {
                    const {cid} = data.instance.options;
                    if (data.instance.state.isFullscreen) {
                        scrollLocker.addLocker(cid);
                    } else {
                        scrollLocker.removeLocker(cid);
                    }
                };
                config.onUpdate = data => {
                    onUpdate(data);
                    updateScrollLocker(data);
                };
                config.onCreate = data => {
                    onCreate(data);
                    updateScrollLocker(data);
                };
            }

            if (this._displayArrow()) {
                const menu = this._getMenuElement();
                let arrow = $(menu).children('.arrow')[0];

                if (!arrow) {
                    arrow = document.createElement(menu.tagName.toLowerCase() === 'ul' ? 'li' : 'span');
                    arrow.classList.add('arrow');
                    arrow.setAttribute('data-helper-element', '');
                    menu.insertBefore(arrow, menu.firstChild);
                }

                config.modifiers.arrow = _.extend(config.modifiers.arrow || {}, {
                    element: arrow,
                    fn: (data, options) => {
                        if (this._checkKeepSeparately()) {
                            data.arrowStyles = _.extend({}, data.arrowStyles || {}, {
                                visibility: 'hidden'
                            });
                        }

                        return Popper.Defaults.modifiers.arrow.fn(data, options);
                    }
                });
            }

            if (_.result(config.modifiers, 'preventOverflow')) {
                const boundariesElement = config.modifiers.preventOverflow.boundariesElement;

                if (boundariesElement && ['scrollParent', 'window', 'viewport'].indexOf(boundariesElement) === -1) {
                    config.modifiers.preventOverflow.boundariesElement = $(this._element).closest(boundariesElement)[0];
                }

                config.modifiers.preventOverflow.escapeWithReference = true;
            }

            return config;
        },

        /**
         * Defined property `_inNavbar` is used only for
         *
         * @return {boolean}
         * @protected
         */
        _detectNavbar() {
            return original._detectNavbar.call(this) ||
                this._config.popper === false || // popper plugin is turned off intentionally
                $(this._element).closest('.app-header').length > 0; // app-header is considered as navbar as well
        },

        _displayArrow() {
            return _.isBoolean(this._config.displayArrow) ? this._config.displayArrow : config.displayArrow;
        },

        _checkKeepSeparately() {
            return _.isBoolean(this._config.keepSeparately) ? this._config.keepSeparately : config.keepSeparately;
        }
    });

    Dropdown._clearMenus = function(event) {
        if (event && (event.type === 'click' || event.type === 'clearMenus')) {
            const $target = $(event.target);
            const $openDropdownMenu = $target.closest('.dropdown-menu.show');

            if ($target.closest('[data-toggle]').length && $openDropdownMenu.length) {
                // click on toggle element inside active dropdown-menu
                return;
            } else if (
                event.type === 'clearMenus' &&
                $openDropdownMenu.length &&
                $.contains($openDropdownMenu[0], event.target)
            ) {
                // click on custom control inside active dropdown-menu
                return;
            }

            if ($target.closest('.dropdown-menu.show').length) {
                // Dropdown._clickEvent is defined only if the click occurred within some opened dropdown menu
                // original click event is used in the hide event handler
                Dropdown._clickEvent = event;
            }
        }

        _clearMenus(event);

        delete Dropdown._clickEvent;
    };

    /**
     * Method `_dataApiKeydownHandler` is copied from bootstrap 4.6 where already fixed issue with Esc keydown event
     */
    Dropdown._dataApiKeydownHandler = function(event) {
        // If not input/textarea:
        //  - And not a key in REGEXP_KEYDOWN => not a dropdown command
        // If input/textarea:
        //  - If space key => not a dropdown command
        //  - If key is other than escape
        //    - If key is not up or down => not a dropdown command
        //    - If trigger inside the menu => not a dropdown command
        if (/input|textarea/i.test(event.target.tagName)
            ? event.which === SPACE_KEYCODE || event.which !== ESCAPE_KEYCODE &&
            (event.which !== ARROW_DOWN_KEYCODE && event.which !== ARROW_UP_KEYCODE ||
                $(event.target).closest(Selector.MENU).length) : !REGEXP_KEYDOWN.test(event.which)) {
            return;
        }

        if (this.disabled || $(this).hasClass(ClassName.DISABLED)) {
            return;
        }

        const parent = Dropdown._getParentFromElement(this);
        const isActive = $(parent).hasClass(ClassName.SHOW);

        if (!isActive && event.which === ESCAPE_KEYCODE) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (!isActive || event.which === ESCAPE_KEYCODE || event.which === SPACE_KEYCODE) {
            if (event.which === ESCAPE_KEYCODE) {
                const toggle = parent.querySelector(Selector.DATA_TOGGLE);
                $(toggle).trigger('focus');
            }

            $(this).trigger('click');
            return;
        }

        const items = [].slice.call($(parent).find(Selector.VISIBLE_ITEMS).get());

        if (items.length === 0) {
            return;
        }

        let index = items.indexOf(event.target);

        if (event.which === ARROW_UP_KEYCODE && index > 0) { // Up
            index--;
        }

        if (event.which === ARROW_DOWN_KEYCODE && index < items.length - 1) { // Down
            index++;
        }

        if (index < 0) {
            index = 0;
        }

        items[index].focus();
    };

    function _events(names) {
        return names.map(name => `${name}${EVENT_KEY}${DATA_API_KEY}`).join(' ');
    }

    $(document)
        // fixed issue when dropdown opens on Esc key down
        .off(_events(['keydown']), Selector.DATA_TOGGLE)
        .off(_events(['keydown']), Selector.MENU)
        .on(_events(['keydown']), Selector.DATA_TOGGLE, Dropdown._dataApiKeydownHandler)
        .on(_events(['keydown']), Selector.MENU, Dropdown._dataApiKeydownHandler)

        // replaced _clearMenus handler with custom one
        .off(_events(['click', 'keyup']), _clearMenus)
        .on(_events(['click', 'keyup', 'clearMenus']), Dropdown._clearMenus)

        // nested form click events are processed in _clearMenus method extend
        .off(_events(['click']), Selector.FORM_CHILD)
        .on(_events(['disposeLayout']), event => {
            $('[data-toggle="dropdown"]', event.target).each(function() {
                const $toogler = $(this);
                if ($toogler.data('bs.dropdown')) {
                    $toogler.dropdown('dispose');
                }
            });
        });

    return Dropdown;
});
