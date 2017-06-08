define([
    'jquery',
    'underscore',
    'oroui/js/dropdown-mask',
    'jquery-ui',
    'jquery.multiselect'
], function($, _, mask) {
    'use strict';

    var oldRefresh = $.ech.multiselect.prototype.refresh;

    $.widget('orofilter.multiselect', $.ech.multiselect, {
        options: _.extend({}, $.ech.multiselect.prototype.options, {
            refreshNotOpened: true
        }),

        /**
         * Bind update position method after menu is opened
         * @override
         */
        open: function() {
            if (!this.hasBeenOpened) {
                this.hasBeenOpened = true;
                this.refresh();
            }
            this._superApply(arguments);
            if (!this.options.appendTo) {
                this.menu.css('zIndex', '');
                var zIndex = Math.max.apply(Math, this.element.parents().add(this.menu).map(function() {
                    var zIndex = Number($(this).css('zIndex'));
                    return isNaN(zIndex) ? 0 : zIndex;
                }));

                this.menu.css('zIndex', zIndex + 2);

                mask.show(zIndex + 1)
                    .onhide($.proxy(this.close, this));
            }
        },

        /**
         * Remove all handlers before closing menu
         * @override
         */
        close: function() {
            mask.hide();
            this._superApply(arguments);
        },

        /**
         * Process position update for menu element
         */
        updatePos: function() {
            var isShown = this.menu.is(':visible');
            this.position();
            if (isShown) {
                this.menu.show();
            }
        },

        refresh: function(init) {
            if (this.hasBeenOpened || this.options.refreshNotOpened) {
                oldRefresh.call(this, init);
            }
        },

        getChecked: function() {
            return this.menu.find('input').not('[type=search]').filter(':checked');
        },

        getUnchecked: function() {
            return this.menu.find('input').not('[type=search]').not(':checked');
        }
    });

    // replace original ech.multiselect widget to make ech.multiselectfilter work
    $.widget('ech.multiselect', $.orofilter.multiselect, {});
});
