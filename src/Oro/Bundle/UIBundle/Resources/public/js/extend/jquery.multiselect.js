define([
    'jquery',
    'underscore',
    'oroui/js/dropdown-mask',
    'jquery-ui',
    'jquery.multiselect'
], function($, _, mask) {
    'use strict';

    $.widget('orofilter.multiselect', $.ech.multiselect, {
        options: _.extend({}, $.ech.multiselect.prototype.options, {
            outerTrigger: null,
            refreshNotOpened: true
        }),

        _create: function(...args) {
            this.outerTrigger = this.options.outerTrigger;
            this._superApply(args);
        },

        _bindEvents: function() {
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

        /**
         * Bind update position method after menu is opened
         * @override
         */
        open: function(...args) {
            if (!this.hasBeenOpened) {
                this.hasBeenOpened = true;
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
        },

        /**
         * Remove all handlers before closing menu
         * @override
         */
        close: function(...args) {
            mask.hide();
            this._superApply(args);
        },

        /**
         * Process position update for menu element
         */
        updatePos: function(position) {
            const menu = this.widget();
            const isShown = menu.is(':visible');

            menu.position(position);
            if (isShown) {
                menu.show();
            }
        },

        refresh: function(init) {
            if (this.hasBeenOpened || this.options.refreshNotOpened) {
                const scrollTop = this.menu.find('.ui-multiselect-checkboxes').scrollTop();
                this._super(init);
                this.menu.find('.ui-multiselect-checkboxes').scrollTop(scrollTop);
            }
        },

        getChecked: function() {
            return this.menu.find('input').not('[type=search]').filter(':checked');
        },

        getUnchecked: function() {
            return this.menu.find('input').not('[type=search]').not(':checked');
        },

        _setMenuHeight: function() {
            this.menu.find('.ui-multiselect-checkboxes li:hidden, .ui-multiselect-checkboxes a:hidden')
                .addClass('hidden-item');
            this._super();
            this.menu.find('.hidden-item').removeClass('hidden-item');
        },

        _isExcluded: function(target) {
            const $target = $(target);
            const isMenu = !!$target.closest(this.menu).length;
            const isButton = !!$target.closest(this.button).length;
            let isOuterTrigger = false;

            if (this.outerTrigger && (this.outerTrigger instanceof $) && this.outerTrigger.length) {
                isOuterTrigger = !!$target.closest(this.outerTrigger).length;
            }

            return !isMenu &&
                   !isButton &&
                   !isOuterTrigger;
        }
    });

    // replace original ech.multiselect widget to make ech.multiselectfilter work
    $.widget('ech.multiselect', $.orofilter.multiselect, {});
});
