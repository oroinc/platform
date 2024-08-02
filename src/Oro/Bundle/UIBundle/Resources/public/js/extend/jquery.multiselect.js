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

        // Modified original _bindButtonEvents to avoid deprecated jQuery methods
        _bindButtonEvents: function() {
            const self = this;
            const button = this.button;
            function clickHandler() {
                self[self._isOpen ? 'close' : 'open']();
                return false;
            }

            // webkit doesn't like it when you click on the span :(
            button
                .find('span')
                .on('click.multiselect', clickHandler);

            // button events
            button.on({
                click: clickHandler,
                keypress: function(e) {
                    switch (e.which) {
                        case 27: // esc
                        case 38: // up
                        case 37: // left
                            self.close();
                            break;
                        case 39: // right
                        case 40: // down
                            self.open();
                            break;
                    }
                },
                mouseenter: function() {
                    if (!button.hasClass('ui-state-disabled')) {
                        $(this).addClass('ui-state-hover');
                    }
                },
                mouseleave: function() {
                    $(this).removeClass('ui-state-hover');
                },
                focus: function() {
                    if (!button.hasClass('ui-state-disabled')) {
                        $(this).addClass('ui-state-focus');
                    }
                },
                blur: function() {
                    $(this).removeClass('ui-state-focus');
                }
            });
        },

        // Modified original _bindHeaderEvents to avoid deprecated jQuery methods
        _superBindHeaderEvents: function() {
            const self = this;
            // header links
            this.header.on('click.multiselect', 'a', function(e) {
                const $this = $(this);
                if ($this.hasClass('ui-multiselect-close')) {
                    self.close();
                } else if ($this.hasClass('ui-multiselect-all')) {
                    self.checkAll();
                } else if ($this.hasClass('ui-multiselect-none')) {
                    self.uncheckAll();
                }
                e.preventDefault();
            }).on('keydown.multiselect', 'a', function(e) {
                switch (e.which) {
                    case 27:
                        self.close();
                        break;
                    case 9:
                        const $target = $(e.target);
                        if (
                            (
                                e.shiftKey &&
                                !$target.parent().prev().length &&
                                !self.header.find('.ui-multiselect-filter').length
                            ) ||
                            (
                                !$target.parent().next().length &&
                                !self.labels.length &&
                                !e.shiftKey
                            )
                        ) {
                            self.close();
                            e.preventDefault();
                        }
                        break;
                }
            });
        },

        _bindHeaderEvents() {
            this._superBindHeaderEvents();

            this.header.off('keydown.multiselect', 'a');
        },

        // Modified original _bindMenuEvents to avoid deprecated jQuery methods
        _superBindMenuEvents: function() {
            const self = this;
            // optgroup label toggle support
            this.menu.on('click.multiselect', '.ui-multiselect-optgroup a', function(e) {
                e.preventDefault();

                const $this = $(this);
                const $inputs = $this.parent().find('input:visible:not(:disabled)');
                const nodes = $inputs.get();
                const label = $this.text();

                // trigger event and bail if the return is false
                if (self._trigger('beforeoptgrouptoggle', e, {inputs: nodes, label: label}) === false) {
                    return;
                }

                // toggle inputs
                self._toggleChecked(
                    $inputs.filter(':checked').length !== $inputs.length,
                    $inputs
                );

                self._trigger('optgrouptoggle', e, {
                    inputs: nodes,
                    label: label,
                    checked: nodes.length ? nodes[0].checked : null
                });
            }).on('mouseenter.multiselect', 'label', function() {
                if (!$(this).hasClass('ui-state-disabled')) {
                    self.labels.removeClass('ui-state-hover');
                    $(this).addClass('ui-state-hover').find('input').trigger('focus');
                }
            }).on('keydown.multiselect', 'label', function(e) {
                if (e.which === 82) {
                    // "r" key, often used for reload.
                    return;
                }
                if (e.which > 111 && e.which < 124) {
                    // Keyboard function keys.
                    return;
                }
                e.preventDefault();
                switch (e.which) {
                    case 9: // tab
                        if (e.shiftKey) {
                            self.menu.find('.ui-state-hover').removeClass('ui-state-hover');
                            self.header.find('li').last().find('a').trigger('focus');
                        } else {
                            self.close();
                        }
                        break;
                    case 27: // esc
                        self.close();
                        break;
                    case 38: // up
                    case 40: // down
                    case 37: // left
                    case 39: // right
                        self._traverse(e.which, this);
                        break;
                    case 13: // enter
                    case 32:
                        $(this).find('input').first().trigger('click');
                        break;
                    case 65:
                        if (e.altKey) {
                            self.checkAll();
                        }
                        break;
                    case 85:
                        if (e.altKey) {
                            self.uncheckAll();
                        }
                        break;
                }
            }).on('click.multiselect', 'input[type="checkbox"], input[type="radio"]', function(e) {
                const $this = $(this);
                const val = this.value;
                const optionText = $this.parent().find('span').text();
                const checked = this.checked;
                const tags = self.element.find('option');

                // bail if this input is disabled or the event is cancelled
                if (
                    this.disabled || self._trigger('click', e, {
                        value: val,
                        text: optionText,
                        checked: checked
                    }) === false
                ) {
                    e.preventDefault();
                    return;
                }

                // make sure the input has focus. otherwise, the esc key
                // won't close the menu after clicking an item.
                $this.trigger('focus');

                // toggle aria state
                $this.prop('aria-selected', checked);

                // change state on the original option tags
                tags.each(function() {
                    if (this.value === val) {
                        this.selected = checked;
                    } else if (!self.options.multiple) {
                        this.selected = false;
                    }
                });

                // some additional single select-specific logic
                if (!self.options.multiple) {
                    self.labels.removeClass('ui-state-active');
                    $this.closest('label').toggleClass('ui-state-active', checked);

                    // close menu
                    self.close();
                }

                // fire change on the select box
                self.element.trigger('change');

                // setTimeout is to fix multiselect issue #14 and #47. caused by jQuery issue #3827
                // http://bugs.jquery.com/ticket/3827
                setTimeout(self.update.bind(self), 10);
            });
        },

        _bindMenuEvents() {
            this._superBindMenuEvents();

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
            this.menu.off('keydown.multiselect', 'label');
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
                        $(e.currentTarget).find('input').trigger('click');
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
        },

        // Modified original open method to avoid deprecated jQuery methods
        _superOpen: function(e) {
            const self = this;
            const button = this.button;
            const menu = this.menu;
            const speed = this.speed;
            const o = this.options;
            const args = [];

            // bail if the multiselectopen event returns false, this widget is disabled, or is already open
            if (this._trigger('beforeopen') === false || button.hasClass('ui-state-disabled') || this._isOpen) {
                return;
            }

            const $container = menu.find('.ui-multiselect-checkboxes');
            const effect = o.show;

            // figure out opening effects/speeds
            if (Array.isArray(o.show)) {
                effect = o.show[0];
                speed = o.show[1] || self.speed;
            }

            // if there's an effect, assume jQuery UI is in use
            // build the arguments to pass to show()
            if (effect) {
                args = [effect, speed];
            }

            // set the scroll of the checkbox container
            $container.scrollTop(0);

            // show the menu, maybe with a speed/effect combo
            $.fn.show.apply(menu, args);

            this._resizeMenu();
            // positon
            this.position();

            // select the first not disabled option or the filter input if available
            const filter = this.header.find('.ui-multiselect-filter');
            if (filter.length) {
                filter.first().find('input').trigger('focus');
            } else if (this.labels.length) {
                this.labels
                    .filter(':not(.ui-state-disabled)')
                    .first()
                    .trigger('mouseover')
                    .trigger('mouseenter')
                    .find('input')
                    .trigger('focus');
            } else {
                this.header.find('a').first().trigger('focus');
            }

            button.addClass('ui-state-active');
            this._isOpen = true;
            this._trigger('open');
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
            this._superOpen(args);
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
            this.menu.prop('tabindex', false);

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
            if (Array.isArray(o.hide)) {
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
                        this.menu.find(`#${activeElement.id}`).trigger('focus');
                    } else if (this.menu.find(activeElement).length && !activeElement.disabled) {
                        this.menu.find(activeElement).trigger('focus');
                    } else {
                        this.menu.trigger('focus');
                    }

                    // Fallback when activeElement was present but can't focused
                    // Keep focus inside menu
                    if (!this.menu[0].contains(document.activeElement)) {
                        this.menu.trigger('focus');
                    }
                }

                $checkboxesContainer.scrollTop(scrollTop);
            }
            this.headerLinkContainer.attr('role', 'presentation');
            this.menu.find('.ui-multiselect-checkboxes').attr({
                'aria-label': this.options.listAriaLabel ? this.options.listAriaLabel : null
            });
        },

        update(isDefault) {
            const o = this.options;
            const $inputs = this.inputs;
            const $checked = $inputs.filter(':checked');
            const numChecked = $checked.length;
            let value;

            if (numChecked === 0) {
                value = o.noneSelectedText;
            } else {
                if (typeof o.selectedText === 'function') {
                    value = o.selectedText.call(this, numChecked, $inputs.length, $checked.get());
                } else if (/\d/.test(o.selectedList) && o.selectedList > 0 && numChecked <= o.selectedList) {
                    value = $checked.map(function() {
                        return $(this).next().text();
                    }).get().join(o.selectedListSeparator);
                } else {
                    value = o.selectedText.replace('#', numChecked).replace('#', $inputs.length);
                }
            }

            this._setButtonValue(value);
            if (isDefault) {
                this.button[0].defaultValue = value;
            }
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

        destroy: function() {
            // remove classes + data
            $.Widget.prototype.destroy.call(this);
            // unbind events
            $(document).off(this._namespaceID);
            $(this.element[0].form).off(this._namespaceID);

            this.button.remove();
            this.menu.remove();
            this.element.show();

            return this;
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
