define(['jquery'], function($) {
    'use strict';

    $.ajaxSetup({
        headers: {
            'X-CSRF-Header': 1
        }
    });
    $.expr[':'].parents = function(a, i, m) {
        return $(a).parents(m[3]).length < 1;
    };

    $.fn.extend({
        /**
         * Sets cursor to end of input
         */
        setCursorPosition: function(index) {
            return this.each(function() {
                var el = this;
                if ('selectionStart' in el && el.type !== 'number') {
                    el.selectionEnd = el.selectionStart = index === 'end' ? el.value.length : index;
                }
            });
        },

        /**
         * Sets cursor to end of input
         */
        setCursorToEnd: function() {
            return this.setCursorPosition('end');
        },

        /**
         * Sets focus on first form field
         */
        focusFirstInput: function() {
            var $input = this.find(':input:visible, [data-focusable]')
                    .not(':checkbox, :radio, :button, :submit, :disabled, :file');
            var $autoFocus = $input.filter('[autofocus]');
            if ($autoFocus.length || $input.length) {
                ($autoFocus.length ? $autoFocus : $input).first().setCursorToEnd().focus();
            }
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
        getBorders: function(el) {
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
         * Inserts string in <textarea> or <input> at the cursor position and sets cursor after inserted data
         *
         * @returns {number}
         */
        insertAtCursor: function(str) {
            return this.each(function() {
                var start;
                var end;
                var el = this;
                var value = el.value;
                if ('selectionStart' in el) {
                    start = el.selectionStart;
                    end = el.selectionEnd;
                    el.value = value.substr(0, start) + str + value.substr(end);
                    el.selectionEnd = el.selectionStart = start + str.length;
                } else {
                    el.value += str;
                }
            });
        },

        /**
         * Thanks http://stackoverflow.com/questions/290254/how-to-order-events-bound-with-jquery
         */
        bindFirst: function(eventType, eventData, handler) {
            var indexOfDot = eventType.indexOf('.');
            var eventNameSpace = indexOfDot > 0 ? eventType.substring(indexOfDot) : '';

            eventType = indexOfDot > 0 ? eventType.substring(0, indexOfDot) : eventType;
            handler = handler === undefined ? eventData : handler;
            eventData = typeof eventData === 'function' ? {} : eventData;

            return this.each(function() {
                var $this = $(this);
                var currentAttrListener = this['on' + eventType];

                if (currentAttrListener) {
                    $this.bind(eventType, function(e) {
                        return currentAttrListener(e.originalEvent);
                    });

                    this['on' + eventType] = null;
                }

                $this.bind(eventType + eventNameSpace, eventData, handler);

                var allEvents = $this.data('events') || $._data($this[0], 'events');
                var typeEvents = allEvents[eventType];
                var newEvent = typeEvents.pop();
                typeEvents.unshift(newEvent);
            });
        }
    });

    return $;
});
