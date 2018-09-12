define(function(require) {
    'use strict';

    var $ = require('jquery');

    var NAME = 'modal';
    var EVENT_KEY = '.bs.modal';
    var Event = {
        FOCUSIN: 'focusin' + EVENT_KEY
    };

    require('bootstrap-modal');

    /**
     * fix endless loop
     * Based on https://github.com/Khan/bootstrap/commit/378ab557e24b861579d2ec4ce6f04b9ea995ab74
     * Updated to support two modals on page
     */
    $.fn[NAME].Constructor.prototype._enforceFocus = function() {
        var that = this;

        $(document).off(Event.FOCUSIN) // Guard against infinite focus loop
            .on(Event.FOCUSIN, function safeSetFocus(event) {
                if (document !== event.target &&
                    that._element !== event.target &&
                    $(that._element).has(event.target).length
                ) {
                    $(document).off(Event.FOCUSIN);
                    that._element.focus();
                    $(document).on(Event.FOCUSIN, safeSetFocus);
                }
            });
    };
});
