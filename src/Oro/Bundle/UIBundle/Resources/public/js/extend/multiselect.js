define([
    'jquery',
    'oroui/js/dropdown-mask',
    'jquery-ui',
    'jquery.multiselect'
], function($, mask) {
    'use strict';

    $.widget('orofilter.multiselect', $.ech.multiselect, {

        /**
         * Bind update position method after menu is opened
         * @override
         */
        open: function() {
            var rect;
            var parentRect;
            var overlap;
            var value;
            this._superApply(arguments);
            mask.show().onhide($.proxy(this.close, this));
            rect = this.menu.get(0).getBoundingClientRect();
            parentRect = this.menu.parent().get(0).getBoundingClientRect();
            overlap =  rect.right - parentRect.right;
            if (overlap > 0) {
                value = parseInt(this.menu.css('left'));
                if (!isNaN(value)) {
                    this.menu.css('left', value - overlap + 'px');
                } else {
                    value = parseInt(this.menu.css('right'));
                    if (!isNaN(value)) {
                        this.menu.css('right', value + overlap + 'px');
                    }
                }
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
        }
    });

    // replace original ech.multiselect widget to make ech.multiselectfilter work
    $.widget('ech.multiselect', $.orofilter.multiselect, {});
});
