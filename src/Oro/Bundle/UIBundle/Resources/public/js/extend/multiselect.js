define([
    'jquery',
    'oroui/js/dropdown-mask',
    'jquery-ui',
    'jquery.multiselect'
], function($, mask) {
    'use strict';

    var oldRefresh = $.ech.multiselect.prototype.refresh;

    $.widget('orofilter.multiselect', $.ech.multiselect, {

        /**
         * Bind update position method after menu is opened
         * @override
         */
        open: function() {
            if (!this.hasBeenOpened) {
                this.hasBeenOpened = true;
                this.refresh(true);
            }
            this._superApply(arguments);
            mask.show().onhide($.proxy(this.close, this));
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
            if (this.hasBeenOpened) {
                oldRefresh.call(this, init);
            }
        }
    });

    // replace original ech.multiselect widget to make ech.multiselectfilter work
    $.widget('ech.multiselect', $.orofilter.multiselect, {});
});
