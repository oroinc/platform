define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    require('bootstrap-modal');

    const NAME = 'modal';
    const EVENT_KEY = '.bs.modal';
    const Event = {
        FOCUSIN: 'focusin' + EVENT_KEY
    };

    const Modal = $.fn[NAME].Constructor;
    const original = _.pick(Modal.prototype, 'dispose');

    Modal.prototype.dispose = function() {
        this._removeBackdrop();
        original.dispose.call(this);
    };

    /**
     * fix endless loop
     * Based on https://github.com/Khan/bootstrap/commit/378ab557e24b861579d2ec4ce6f04b9ea995ab74
     * Updated to support two modals on page
     */
    Modal.prototype._enforceFocus = function() {
        const that = this;

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
