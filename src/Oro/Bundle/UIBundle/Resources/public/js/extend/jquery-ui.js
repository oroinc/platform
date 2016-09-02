define(['jquery', 'jquery-ui'], function($) {
    'use strict';

    /* datepicker extend:start */
    (function() {

        var original = {
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
            // jshint -W040
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

            if (!isActualClass && inst.dpDiv.is(':visible')) {
                $input.toggleClass(dropdownClassName, isBelow);
                $input.toggleClass(dropupClassName, !isBelow);
                $input.trigger('datepicker:dialogReposition', isBelow ? 'below' : 'above');
            }
        }

        var _showDatepicker = $.datepicker.constructor.prototype._showDatepicker;
        var _hideDatepicker = $.datepicker.constructor.prototype._hideDatepicker;

        /**
         * Bind update position method after datepicker is opened
         *
         * @param elem
         * @override
         * @private
         */
        $.datepicker.constructor.prototype._showDatepicker = function(elem) {
            _showDatepicker.apply(this, arguments);

            var input = elem.target || elem;
            var $input = $(input);
            var events = getEvents($input.id);

            $input
                .removeClass(dropdownClassName + ' ' + dropupClassName)
                .parents().add(window).each(function() {
                    $(this).on(events, $.proxy(updatePos, input));
                    // @TODO develop other approach than hide on scroll
                    // because on mobile devices it's impossible to open calendar without scrolling
                    /*$(this).on(events, function () {
                        // just close datepicker
                        $.datepicker._hideDatepicker();
                        input.blur();
                    });*/
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

            _hideDatepicker.apply(this, arguments);

            $input.trigger('datepicker:dialogHide');
        };

        $.datepicker.constructor.prototype._destroyDatepicker = function() {
            if (!this._curInst) {
                return;
            }
            if (this._curInst.dpDiv) {
                this._curInst.dpDiv.remove();
            }
            original._destroyDatepicker.apply(this, arguments);
        };
    }());
    $(document).off('select2-open.dropdown.data-api').on('select2-open.dropdown.data-api', function() {
        if ($.datepicker._curInst && $.datepicker._datepickerShowing && !($.datepicker._inDialog && $.blockUI)) {
            $.datepicker._hideDatepicker();
        }
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
                if (typeof this.uiDialog.css('z-index') === 'string') {
                    this.uiDialog.css('z-index', 910);
                }
                oldMoveToTop.apply(this);
            }
        });
    }());
    /* dialog extend:end*/
});
