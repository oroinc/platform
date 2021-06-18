define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mask = require('oroui/js/dropdown-mask');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    const KEY_CODES = require('oroui/js/tools/keyboard-key-codes').default;
    require('jquery-ui/widget');
    require('jquery.multiselect');

    $.widget('orofilter.multiselect', $.ech.multiselect, {
        options: _.extend({}, $.ech.multiselect.prototype.options, {
            outerTrigger: null,
            refreshNotOpened: true
        }),

        _create(...args) {
            this._uniqueName = _.uniqueId(this.widgetName);
            this.$outerTrigger = $(this.options.outerTrigger);
            this.initialValue = this.element.val();

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

        _bindEvents() {
            const self = this;

            this._bindButtonEvents();
            this._bindMenuEvents();
            this._bindHeaderEvents();

            // close each widget when clicking on any other element/anywhere else on the page
            $(document).on('mousedown.' + self._namespaceID, function(event) {
                if (self._isOpen && self._isExcluded(event.target)) {
                    self.close();
                }
            });

            // deal with form resets.  the problem here is that buttons aren't
            // restored to their defaultValue prop on form reset, and the reset
            // handler fires before the form is actually reset.  delaying it a bit
            // gives the form inputs time to clear.
            $(this.element[0].form).on('reset.' + this._namespaceID, function() {
                setTimeout(self.refresh.bind(self), 10);
            });
        },

        _bindMenuEvents() {
            const superResult = this._super();

            // Remove original an event handler and attach new one based on original
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
                    case KEY_CODES.SPACE:
                        e.preventDefault();
                        $(e.currentTarget).find('input').click();
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
                manageFocus.preventTabOutOfContainer(e, this.menu);

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
                // Actualize initial value when dropdown will be opened first time because
                this.initialValue = this.element.val();
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
        },

        close() {
            mask.hide();
            this.button.attr('aria-expanded', false);
            this.$outerTrigger.attr('aria-expanded', false);

            const superResult = this._superApply();

            if ($.contains(this.menu[0], document.activeElement)) {
                this.button.trigger('focus');

                // move focus to $outerTrigger element in case own multiselect button is hidden
                if (!this.button.is(':tabbable')) {
                    this.$outerTrigger.trigger('focus');
                }
            }

            return superResult;
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
                const scrollTop = this.menu.find('.ui-multiselect-checkboxes').scrollTop();
                this._super(init);
                this.menu.find('.ui-multiselect-checkboxes').scrollTop(scrollTop);
            }
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
