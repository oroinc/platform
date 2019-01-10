define(function(require) {
    'use strict';

    var $ = require('jquery');
    var mask = require('oroui/js/dropdown-mask');
    require('jquery-ui');

    /* datepicker extend:start */
    (function() {
        var original = {
            _showDatepicker: $.datepicker.constructor.prototype._showDatepicker,
            _hideDatepicker: $.datepicker.constructor.prototype._hideDatepicker,
            _attachments: $.datepicker.constructor.prototype._attachments,
            _updateDatepicker: $.datepicker.constructor.prototype._updateDatepicker,
            _destroyDatepicker: $.datepicker.constructor.prototype._destroyDatepicker
        };

        var dropdownClassName = 'ui-datepicker-dialog-is-below';
        var dropupClassName = 'ui-datepicker-dialog-is-above';

        /**
         * Combines space-separated line of events with widget's namespace
         *  for handling datepicker's position change
         *
         * @returns {string}
         * @private
         */
        function getEvents(uuid) {
            var events = ['scroll', 'resize'];
            var ns = 'datepicker-' + uuid;

            events = $.map(events, function(eventName) {
                return eventName + '.' + ns;
            });

            return events.join(' ');
        }

        /**
         * Process position update for datepicker element
         */
        function updatePos() {
            var pos;
            var isFixed;
            var offset;
            var input = this;
            var $input = $(this);

            var inst = $.datepicker._getInst(input);
            if (!inst) {
                return;
            }

            if (!$.datepicker._pos) { // position below input
                pos = $.datepicker._findPos(input);
                pos[1] += input.offsetHeight; // add the height
            }

            isFixed = false;
            $input.parents().each(function() {
                isFixed = isFixed || $(this).css('position') === 'fixed';
                return !isFixed;
            });

            offset = {left: pos[0], top: pos[1]};
            offset = $.datepicker._checkOffset(inst, offset, isFixed);
            inst.dpDiv.css({left: offset.left + 'px', top: offset.top + 'px'});

            var isBelow = offset.top - $input.offset().top > 0;
            var isActualClass = $input.hasClass(dropdownClassName) === isBelow &&
                $input.hasClass(dropupClassName) !== isBelow;

            if (!isActualClass && inst.dpDiv.is(':visible') && $.datepicker._lastInput === input) {
                $input.toggleClass(dropdownClassName, isBelow);
                $input.toggleClass(dropupClassName, !isBelow);
                $input.trigger('datepicker:dialogReposition', isBelow ? 'below' : 'above');
            }
        }

        $.datepicker.constructor.prototype._attachments = function($input, inst) {
            $input
                .off('click', this._showDatepicker)
                .click(this._showDatepicker);
            original._attachments.call(this, $input, inst);
        };

        /**
         * Bind update position method after datepicker is opened
         *
         * @param elem
         * @override
         * @private
         */
        $.datepicker.constructor.prototype._showDatepicker = function(elem) {
            original._showDatepicker.apply(this, arguments);

            var input = elem.target || elem;
            var $input = $(input);
            var events = getEvents($input.id);

            var inst = $.datepicker._getInst(input);
            // set bigger zIndex difference between dropdown and input, to have place for dropdown mask
            inst.dpDiv.css('z-index', Number(inst.dpDiv.css('z-index')) + 2);

            $input
                .removeClass(dropdownClassName + ' ' + dropupClassName)
                .parents().add(window).each(function() {
                    $(this).on(events, $.proxy(updatePos, input));
                    // @TODO develop other approach than hide on scroll
                    // because on mobile devices it's impossible to open calendar without scrolling
                    /* $(this).on(events, function () {
                        // just close datepicker
                        $.datepicker._hideDatepicker();
                        input.blur();
                    }); */
                });

            updatePos.call(input);

            $input.trigger('datepicker:dialogShow');
        };

        /**
         * Remove all handlers before closing datepicker
         *
         * @param elem
         * @override
         * @private
         */
        $.datepicker.constructor.prototype._hideDatepicker = function(elem) {
            var input = elem;
            var dpDiv = $.datepicker._curInst.dpDiv;

            if (!elem) {
                if (!$.datepicker._curInst) {
                    return;
                }
                input = $.datepicker._curInst.input.get(0);
            }
            var events = getEvents(input.id);

            var $input = $(input);
            $input
                .removeClass(dropdownClassName + ' ' + dropupClassName)
                .parents().add(window).each(function() {
                    $(this).off(events);
                });

            dpDiv.trigger('content:remove', dpDiv);

            original._hideDatepicker.apply(this, arguments);

            $input.trigger('datepicker:dialogHide');
        };

        $.datepicker.constructor.prototype._updateDatepicker = function(inst) {
            original._updateDatepicker.call(this, inst);

            inst.dpDiv.trigger('content:changed', inst.dpDiv);
        };

        $.datepicker.constructor.prototype._destroyDatepicker = function() {
            if (!this._curInst) {
                return;
            }
            if (this._curInst.input) {
                this._curInst.input.datepicker('hide')
                    .off('click', this._showDatepicker);
            }
            original._destroyDatepicker.apply(this, arguments);
        };
    }());
    $(document).off('select2-open.dropdown.data-api').on('select2-open.dropdown.data-api', function() {
        if ($.datepicker._curInst && $.datepicker._datepickerShowing && !($.datepicker._inDialog && $.blockUI)) {
            $.datepicker._hideDatepicker();
        }
    });
    $(document)
        .on('datepicker:dialogShow', function(e) {
            var $input = $(e.target);
            var zIndex = $.datepicker._getInst(e.target).dpDiv.css('zIndex');
            mask.show(zIndex - 1)
                .onhide(function() {
                    $input.datepicker('hide');
                });
        })
        .on('datepicker:dialogHide', function(e) {
            mask.hide();
        });
    /* datepicker extend:end */

    /* dialog extend:start*/
    (function() {
        var oldMoveToTop = $.ui.dialog.prototype._moveToTop;
        $.widget('ui.dialog', $.ui.dialog, {
            /**
             * Replace method because some browsers return string 'auto' if property z-index not specified.
             * */
            _moveToTop: function() {
                var zIndex = this.uiDialog.css('z-index');
                var numberRegexp = /^\d+$/;
                if (typeof zIndex === 'string' && !numberRegexp.test(zIndex)) {
                    this.uiDialog.css('z-index', 910);
                }
                oldMoveToTop.apply(this);
            }
        });
    }());
    /* dialog extend:end*/

    /* sortable extend:start*/
    (function() {
        var touchHandled;

        /**
         * Simulate a mouse event based on a corresponding touch event
         * @param {Object} event A touch event
         * @param {String} simulatedType The corresponding mouse event
         */
        function simulateMouseEvent(event, simulatedType) {
            // Ignore multi-touch events
            if (event.originalEvent.touches.length > 1) {
                return;
            }

            // event.preventDefault();

            var touch = event.originalEvent.changedTouches[0];

            // Initialize the simulated mouse event using the touch event's coordinates
            var simulatedEvent = new MouseEvent(simulatedType, {
                bubbles: true,
                cancelable: true,
                view: window,
                detail: 1,
                screenX: touch.screenX,
                screenY: touch.screenY,
                clientX: touch.clientX,
                clientY: touch.clientY,
                ctrlKey: false,
                altKey: false,
                shiftKey: false,
                metaKey: false,
                button: 0,
                relatedTarget: null
            });

            // Dispatch the simulated event to the target element
            event.target.dispatchEvent(simulatedEvent);
        }

        $.widget('ui.sortable', $.ui.sortable, {
            /**
             * Handle the jQuery UI widget's touchstart events
             * @param {Object} event The widget element's touchstart event
             */
            _touchStart: function(event) {
                // Ignore the event if another widget is already being handled
                if (touchHandled || !this._mouseCapture(event.originalEvent.changedTouches[0])) {
                    return;
                }

                event.stopPropagation();
                event.preventDefault();

                // Set the flag to prevent other widgets from inheriting the touch event
                touchHandled = true;

                // Simulate the mousedown event
                simulateMouseEvent(event, 'mousedown');
            },

            /**
             * Handle the jQuery UI widget's touchmove events
             * @param {Object} event The document's touchmove event
             */
            _touchMove: function(event) {
                // Ignore event if not handled
                if (!touchHandled) {
                    return;
                }

                event.preventDefault();

                // Simulate the mousemove event
                simulateMouseEvent(event, 'mousemove');
            },

            /**
             * Handle the jQuery UI widget's touchend events
             * @param {Object} event The document's touchend event
             */
            _touchEnd: function(event) {
                // Ignore event if not handled
                if (!touchHandled) {
                    return;
                }

                event.stopPropagation();
                event.preventDefault();

                // Simulate the mouseup event

                simulateMouseEvent(event, 'mouseup');
                // Unset the flag to allow other widgets to inherit the touch event
                touchHandled = false;

                return true;
            },

            /**
             * Method _mouseInit extends $.ui.mouse widget with bound touch event handlers that
             * translate touch events to mouse events and pass them to the widget's
             * original mouse event handling methods.
             */
            _mouseInit: function() {
                // Delegate the touch handlers to the widget's element
                this.element.on({
                    touchstart: $.proxy(this, '_touchStart'),
                    touchmove: $.proxy(this, '_touchMove'),
                    touchend: $.proxy(this, '_touchEnd')
                });

                this._touchMoved = false;

                this._superApply(arguments);
            },

            /**
             * Remove the touch event handlers
             */
            _mouseDestroy: function() {
                this.element.off({
                    touchstart: $.proxy(this, '_touchStart'),
                    touchmove: $.proxy(this, '_touchMove'),
                    touchend: $.proxy(this, '_touchEnd')
                });

                this._superApply(arguments);
            },

            /**
             * Faster and rough handle class setting method
             */
            _setHandleClassName: function() {
                this._removeClass(this.element.find('.ui-sortable-handle'), 'ui-sortable-handle');

                this._addClass(
                    this.options.handle ? this.element.find(this.options.handle) : $($.map(this.items, function(item) {
                        return item.item.get(0);
                    })),
                    'ui-sortable-handle'
                );
            }
        });
    }());
    /* sortable extend:end*/
});
