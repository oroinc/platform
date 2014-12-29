/*global define*/
define(['jquery'], function ($) {
    'use strict';
    $.ajaxSetup({
        headers: {
            'X-CSRF-Header': 1
        }
    });
    $.expr[':'].parents = function (a, i, m) {
        return $(a).parents(m[3]).length < 1;
    };
    // used to indicate app's activity, such as AJAX request or redirection, etc.
    $.isActive = $.proxy(function (flag) {
        if ($.type(flag) !== 'undefined') {
            this.active = flag;
        }
        return $.active || this.active;
    }, {active: false});

    // http://stackoverflow.com/questions/4609405/set-focus-after-last-character-in-text-box
    $.fn.focusAndSetCaretAtEnd = function () {
        if (!this.length)
            return;
        var elem = this[0], elemLen = elem.value.length;
        // For IE Only
        if (document.selection) {
            // Set focus
            $(elem).focus();
            // Use IE Ranges
            var oSel = document.selection.createRange();
            // Reset position to 0 & then set at end
            oSel.moveStart('character', -elemLen);
            oSel.moveStart('character', elemLen);
            oSel.moveEnd('character', 0);
            oSel.select();
        }
        else if (elem.selectionStart || elem.selectionStart == '0') {
            // Firefox/Chrome
            elem.selectionStart = elemLen;
            elem.selectionEnd = elemLen;
            $(elem).focus();
        } // if
    }

    return $;
});
