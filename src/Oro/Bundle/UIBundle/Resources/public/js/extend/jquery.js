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

    $.fn.extend({
        // http://stackoverflow.com/questions/4609405/set-focus-after-last-character-in-text-box
        focusAndSetCaretAtEnd: function () {
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
        },

        /**
         * Sets focus on first form field
         */
        focusFirstInput: function () {
            var $autoFocus,
                $input = this.find(':input:visible, [data-focusable]')
                    .not(':checkbox, :radio, :button, :submit, :disabled, :file');
            $autoFocus = $input.filter('[autofocus]');
            ($autoFocus.length ? $autoFocus : $input).first().focus();
        },

        focus: (function(orig) {
            return function() {
                var $elem = $(this);
                if (!arguments.length && $elem.attr('data-focusable')) {
                    // the element has own implementation to set focus
                    $elem.triggerHandler('set-focus');
                    return $elem;
                } else {
                    return orig.apply(this, arguments);
                }
            };
        })($.fn.focus),

        /**
         * source http://stackoverflow.com/questions/13607252/getting-border-width-in-jquery
         */
        getBorders: function (el) {
            var computed = window.getComputedStyle(el || this[0], null);
            function convertBorderToPx(cssValue) {
                switch (cssValue) {
                    case 'thin':
                        return 1;
                    case 'medium':
                        return 2;
                    case 'thick':
                        return 5;
                    default:
                        return Math.round(parseFloat(cssValue));
                }
            }

            return {
                top: convertBorderToPx(computed.getPropertyValue('borderTopWidth') ||
                    computed.borderTopWidth),
                bottom: convertBorderToPx(computed.getPropertyValue('borderBottomWidth') ||
                    computed.borderBottomWidth),
                left: convertBorderToPx(computed.getPropertyValue('borderLeftWidth') ||
                    computed.borderLeftWidth),
                right: convertBorderToPx(computed.getPropertyValue('borderRightWidth') ||
                    computed.borderRightWidth)
            };
        },

        /**
         * Returns current cursor position in <textarea> or <input>
         *
         * @returns {number}
         */
        getCursorPosition: function() {
            var el = $(this).get(0);
            var pos = 0;
            if('selectionStart' in el) {
                pos = el.selectionStart;
            } else if('selection' in document) {
                el.focus();
                var Sel = document.selection.createRange();
                var SelLength = document.selection.createRange().text.length;
                Sel.moveStart('character', -el.value.length);
                pos = Sel.text.length - SelLength;
            }
            return pos;
        }

    });

    return $;
});
