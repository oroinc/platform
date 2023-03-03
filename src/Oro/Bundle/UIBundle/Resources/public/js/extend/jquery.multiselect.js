define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mask = require('oroui/js/dropdown-mask');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    const KEY_CODES = require('oroui/js/tools/keyboard-key-codes').default;
    require('jquery-ui/widget');
    require('jquery.multiselect');
    require('jquery-ui/tabbable');

    $.widget('orofilter.multiselect', $.ech.multiselect, {
        options: _.extend({}, $.ech.multiselect.prototype.options, {
            outerTrigger: null,
            refreshNotOpened: true,
            preventTabOutOfContainer: true
        }),

        _create(...args) {
            this._uniqueName = _.uniqueId(this.widgetName);
            this.$outerTrigger = $(this.options.outerTrigger);
            this.initialValue = this.options.initialValue || this.element.val();

            const superResult = this._superApply(args);
            const labelledby = [];

            if (this.button.is(':tabbable')) {
                this.button.attr({
                    'id': this._uniqueName,
                    'aria-haspopup': true,
                    'aria-expanded': false
                });
                labelledby.push(this._uniqueName);
            }

            if (this.$outerTrigger.length) {
                this.$outerTrigger.attr({
                    'id': this.$outerTrigger.attr('id') || this._uniqueName,
                    'aria-haspopup': true,
                    'aria-expanded': false
                });
                labelledby.push(this.$outerTrigger.attr('id'));
            }

            if (labelledby.length) {
                this.menu.attr('aria-labelledby', labelledby.join(' '));
            }

            return superResult;
        },

        _makeOption(option) {
            const $item = this._super(option);
            const count = option.getAttribute('data-option-count');
            if (count !== null) {
                $item
                    .find('label span')
                    .text(`${option.label}\xa0(${count})`);
            }
            return $item;
        },

        _bindEvents() {
            this._bindButtonEvents();
            this._bindMenuEvents();
            this._bindHeaderEvents();

            const events = ['mousedown', 'clearMenus']
                .map(eventName => `${eventName}${this._namespaceID}`)
                .join(' ');

            // close each widget when clicking on any other element/anywhere else on the page
            $(document).on(events, event => {
                if (this._isOpen && this._isExcluded(event.target)) {
                    this.close();
                }
            });

            // deal with form resets.  the problem here is that buttons aren't
            // restored to their defaultValue prop on form reset, and the reset
            // handler fires before the form is actually reset.  delaying it a bit
            // gives the form inputs time to clear.
            $(this.element[0].form).on(`reset${this._namespaceID}`, () => {
                setTimeout(this.refresh.bind(this), 10);
            });
        },

        _bindHeaderEvents() {
            const superResult = this._super();

            this.header.undelegate('a', 'keydown.multiselect');

            return superResult;
        },

        _bindMenuEvents() {
            const superResult = this._super();

            // Fix for Firefox accidentally triggering click after focus change on space
            // https://github.com/medialize/ally.js/issues/162
            // https://stackoverflow.com/questions/20863515/in-firefox-changing-the-focus-in-a-keydown-event-handler-automatically-clicks-w
            this.menu.on(`keyup${this._namespaceID}`, 'label', e => {
                if (this._allowFireEventBySpaceButton === void 0 && e.keyCode === KEY_CODES.SPACE) {
                    e.preventDefault();
                }
                delete this._allowFireEventBySpaceButton;
            });
            // Remove original keydown an event handler and attach new one based on original
            this.menu.undelegate('label', 'keydown.multiselect');
            this.menu.on(`keydown${this._namespaceID}`, 'label', e => {
                switch (e.which) {
                    case KEY_CODES.TAB:
                        this.menu.find('.ui-state-hover').removeClass('ui-state-hover');
                        break;
                    case KEY_CODES.ARROW_UP:
                    case KEY_CODES.ARROW_DOWN:
                    case KEY_CODES.ARROW_LEFT:
                    case KEY_CODES.ARROW_RIGHT:
                        e.preventDefault();
                        this._traverse(e.which, e.currentTarget);
                        break;
                    case KEY_CODES.ENTER:
                        e.preventDefault();
                        $(e.currentTarget).find('input').click();
                        break;
                    case KEY_CODES.SPACE:
                        this._allowFireEventBySpaceButton = true;
                        break;
                    case KEY_CODES.A:
                        if (e.altKey) {
                            this.checkAll();
                        }
                        break;
                    case KEY_CODES.U:
                        if (e.altKey) {
                            this.uncheckAll();
                        }
                        break;
                }
            });
            this.menu.on(`keydown${this._namespaceID}`, e => {
                if (this.options.preventTabOutOfContainer) {
                    manageFocus.preventTabOutOfContainer(e, this.menu);
                }

                if (e.which === KEY_CODES.ESCAPE) {
                    this.close();
                }
            });

            return superResult;
        },

        /**
         * Bind update position method after menu is opened
         * @override
         */
        open(...args) {
            if (!this.hasBeenOpened) {
                this.hasBeenOpened = true;
                // Actualize initial value when dropdown will be opened first time,
                // because in runtime some options might get disabled
                const options = this.element.children(':enabled');
                if (this.initialValue instanceof Array) {
                    this.initialValue = this.initialValue.filter(name => options.is(`[value="${name}"]`));
                }
                this.refresh();
            }
            this._superApply(args);
            if (!this.options.appendTo) {
                this.menu.css('zIndex', '');
                const zIndex = Math.max(...this.element.parents().add(this.menu).map(function() {
                    const zIndex = Number($(this).css('zIndex'));
                    return isNaN(zIndex) ? 0 : zIndex;
                }));

                this.menu.css('zIndex', zIndex + 2);

                mask.show(zIndex + 1)
                    .onhide(this.close.bind(this));
            }
            this.button.attr('aria-expanded', true);
            this.$outerTrigger.attr('aria-expanded', true);

            if (this.options.preventTabOutOfContainer) {
                // Remove outdated classes
                this.menu.find('.ui-state-hover').removeClass('ui-state-hover');
                this.menu.find('.focus-visible').removeClass('focus-visible');
                manageFocus.focusTabbable(this.menu);
            }

            this.menu.attr('tabindex', '-1');
            this.button.trigger('clearMenus'); // hides all opened dropdown menus
            this._trigger('opened');
        },

        /**
         * Fully override the original method with modification.
         * Remove elements attributes and fixed moving focus back to a toggle element.
         * Remove all handlers before closing menu.
         * @override
         */
        close() {
            if (this._trigger('beforeclose') === false) {
                return;
            }

            mask.hide();
            this.button.attr('aria-expanded', false);
            this.$outerTrigger.attr('aria-expanded', false);
            this.menu.removeAttr('tabindex');

            this.button.removeClass('ui-state-active');

            if (
                this.options.preventTabOutOfContainer &&
                (
                    $.contains(this.menu[0], document.activeElement) ||
                    this.menu[0].isSameNode(document.activeElement)
                )
            ) {
                this.button.trigger('focus');

                // move focus to $outerTrigger element in case own multiselect button is hidden
                if (!this.button.is(':tabbable')) {
                    this.$outerTrigger.trigger('focus');
                }
            }

            const o = this.options;
            let effect = o.hide;
            let speed = this.speed;
            let args = [];

            // figure out opening effects/speeds
            if ($.isArray(o.hide)) {
                effect = o.hide[0];
                speed = o.hide[1] || this.speed;
            }

            if (effect) {
                args = [effect, speed];
            }

            $.fn.hide.apply(this.menu, args);
            this._isOpen = false;
            this._trigger('close');
        },

        /**
         * Process position update for menu element
         */
        updatePos(position) {
            const menu = this.widget();
            const isShown = menu.is(':visible');

            menu.position(position);
            if (isShown) {
                menu.show();
            }
        },

        refresh(init) {
            if (this.hasBeenOpened || this.options.refreshNotOpened) {
                let $checkboxesContainer = this.menu.find('.ui-multiselect-checkboxes');
                const scrollTop = this.menu.find('.ui-multiselect-checkboxes').scrollTop();
                let {activeElement} = document;

                if (!this.menu[0].contains(activeElement)) {
                    activeElement = null;
                }

                this._super(init);

                // updated checkbox container
                $checkboxesContainer = this.menu.find('.ui-multiselect-checkboxes');
                if (activeElement) {
                    if (activeElement.id) {
                        this.menu.find(`#${activeElement.id}`).focus();
                    } else if (this.menu.find(activeElement).length && !activeElement.disabled) {
                        this.menu.find(activeElement).focus();
                    } else {
                        this.menu.focus();
                    }

                    // Fallback when activeElement was present but can't focused
                    // Keep focus inside menu
                    if (!this.menu[0].contains(document.activeElement)) {
                        this.menu.focus();
                    }
                }

                $checkboxesContainer.scrollTop(scrollTop);
            }
            this.headerLinkContainer.attr('role', 'presentation');
            this.menu.find('.ui-multiselect-checkboxes').attr({
                'aria-label': this.options.listAriaLabel ? this.options.listAriaLabel : null
            });
        },

        getChecked() {
            return this.menu.find('input').not('[type=search]').filter(':checked');
        },

        getUnchecked() {
            return this.menu.find('input').not('[type=search]').not(':checked');
        },

        _getMinWidth() {
            const width = this.options.minWidth;

            if (['auto', 'none'].includes(width)) {
                return width;
            }

            return this._super();
        },

        _setButtonWidth() {
            const width = this._getMinWidth();

            if (width === 'auto') {
                this.button.outerWidth(width);
            } else if (width !== 'none') {
                this._super();
            }
        },

        _setMenuHeight() {
            this.menu.find('.ui-multiselect-checkboxes li:hidden, .ui-multiselect-checkboxes a:hidden')
                .addClass('hidden-item');
            this.menu.find('.hidden-item').removeClass('hidden-item');
        },

        _isExcluded(target) {
            const $target = $(target);
            const isMenu = !!$target.closest(this.menu).length;
            const isButton = !!$target.closest(this.button).length;
            let isOuterTrigger = false;

            if (this.$outerTrigger.length) {
                isOuterTrigger = !!$target.closest(this.$outerTrigger).length;
            }

            return !isMenu &&
                   !isButton &&
                   !isOuterTrigger;
        },

        _destroy() {
            this.menu.off(`keydown${this._namespaceID}`, 'label');
            this.menu.off(`keydown${this._namespaceID}`);
            delete this.initialValue;
            return this._super();
        }
    });

    // replace original ech.multiselect widget to make ech.multiselectfilter work
    $.widget('ech.multiselect', $.orofilter.multiselect, {});
});
