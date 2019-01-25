/* global Window, HTMLDocument */
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

    $.Deferred.getStackHook = function() {
        // Throw an error so we can extract the stack from the Error
        try {
            throw new Error('Exception in jQuery.Deferred');
        } catch (err) {
            return err.stack;
        }
    };

    $.fn.extend({
        focus: (function(originalFocus) {
            return function() {
                var $elem = $(this);
                if (!arguments.length && $elem.attr('data-focusable')) {
                    // the element has own implementation to set focus
                    $elem.triggerHandler('set-focus');
                    return $elem;
                } else {
                    return originalFocus.apply(this, arguments);
                }
            };
        })($.fn.focus),

        offset: (function(originalOffset) {
            return function() {
                if (!arguments.length) {
                    if (this[0] instanceof HTMLDocument) {
                        return originalOffset.call($(this[0].documentElement));
                    }
                    if (this[0] instanceof Window) {
                        return originalOffset.call($(this[0].document.documentElement));
                    }
                }
                return originalOffset.apply(this, arguments);
            };
        })($.fn.offset),

        /**
         * Sets cursor to end of input
         */
        setCursorPosition: function(index) {
            return this.each(function() {
                var el = this;
                if ('selectionStart' in el) {
                    try {
                        el.selectionEnd = el.selectionStart = index === 'end' ? el.value.length : index;
                    } catch (ex) {
                        // avoid exeption when use these actions with unsupported input types (email, number etc)
                    }
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
            var $autoFocus = $input.filter('[autofocus], [data-autofocus]');
            if ($autoFocus.length || $input.length) {
                var $element = ($autoFocus.length ? $autoFocus : $input).first();
                if ($element.isInViewPort()) {
                    $element.setCursorToEnd().focus();
                }
            }
        },

        isInViewPort: function() {
            var $element = $(this);
            var elementTop = $element.offset().top;
            var elementBottom = elementTop + $element.height();
            var viewPortTop = $(window).scrollTop();
            var viewPortBottom = viewPortTop + $(window).height();

            return (
                (elementTop >= viewPortTop) && (elementBottom <= viewPortBottom)
            );
        },

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
                    // avoid exeption when use these actions with unsupported input types (email, number etc)
                    try {
                        start = el.selectionStart;
                        end = el.selectionEnd;
                        el.value = value.substr(0, start) + str + value.substr(end);
                        el.selectionEnd = el.selectionStart = start + str.length;
                    } catch (ex) {
                        el.value += str;
                    }
                } else {
                    el.value += str;
                }
            });
        },

        /**
         * Thanks http://stackoverflow.com/questions/290254/how-to-order-events-bound-with-jquery
         */
        bindFirst: function(eventType, selector, eventData, handler) {
            var indexOfDot = eventType.indexOf('.');
            var eventNameSpace = indexOfDot > 0 ? eventType.substring(indexOfDot) : '';

            eventType = indexOfDot > 0 ? eventType.substring(0, indexOfDot) : eventType;
            handler = handler !== undefined ? handler : (eventData !== undefined ? eventData : selector);
            eventData = typeof eventData !== 'function' ? eventData : undefined;
            selector = typeof selector !== 'function' ? selector : undefined;

            return this.each(function() {
                var $this = $(this);
                var currentAttrListener = this['on' + eventType];

                if (currentAttrListener) {
                    $this.bind(eventType, function(e) {
                        return currentAttrListener(e.originalEvent);
                    });

                    this['on' + eventType] = null;
                }

                $this.on(eventType + eventNameSpace, selector, eventData, handler);

                var allEvents = $this.data('events') || $._data($this[0], 'events');
                var typeEvents = allEvents[eventType];
                var newEvent = typeEvents.pop();
                typeEvents.unshift(newEvent);
            });
        },

        /**
         * Temporarily adds class to element
         */
        addClassTemporarily: function(className, delay) {
            delay = delay || 0;
            return this.each(function() {
                var $el = $(this);
                $el.addClass(className);
                setTimeout(function() {
                    $el.removeClass(className);
                }, delay);
            });
        },

        formFieldValues: function(data) {
            var els = this.find(':input').get();

            if (arguments.length === 0) {
                // return all data
                data = {};
                $.each(els, function() {
                    if (this.name && !this.disabled && (
                        this.checked ||
                            /select|textarea/i.test(this.nodeName) ||
                            /text|hidden|password/i.test(this.type))
                    ) {
                        if (data[this.name] === void 0) {
                            data[this.name] = [];
                        }
                        data[this.name].push($(this).val());
                    }
                });
                return data;
            } else {
                $.each(els, function() {
                    if (this.name && data[this.name]) {
                        var names = data[this.name];
                        var $this = $(this);
                        if (Object.prototype.toString.call(names) !== '[object Array]') {
                            names = [names]; // backwards compat to old version of this code
                        }
                        if (this.type === 'checkbox' || this.type === 'radio') {
                            var val = $this.val();
                            var found = false;
                            for (var i = 0; i < names.length; i++) {
                                if (names[i] === val) {
                                    found = true;
                                    break;
                                }
                            }
                            $this.attr('checked', found);
                        } else {
                            $this.val(names[0]);
                        }
                    }
                });
                return this;
            }
        }
    });

    return $;
});
