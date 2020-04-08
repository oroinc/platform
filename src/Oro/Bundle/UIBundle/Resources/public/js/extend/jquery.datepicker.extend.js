import $ from 'jquery';
import 'jquery-ui';
import moment from 'moment';
import manageFocus from 'oroui/js/tools/manage-focus';

const original = {
    _connectDatepicker: $.datepicker.constructor.prototype._connectDatepicker,
    _attachHandlers: $.datepicker.constructor.prototype._attachHandlers,
    _doKeyDown: $.datepicker.constructor.prototype._doKeyDown,
    _generateHTML: $.datepicker.constructor.prototype._generateHTML,
    _showDatepicker: $.datepicker.constructor.prototype._showDatepicker,
    _hideDatepicker: $.datepicker.constructor.prototype._hideDatepicker,
    _attachments: $.datepicker.constructor.prototype._attachments,
    _updateDatepicker: $.datepicker.constructor.prototype._updateDatepicker,
    _destroyDatepicker: $.datepicker.constructor.prototype._destroyDatepicker
};

const dropdownClassName = 'ui-datepicker-dialog-is-below';
const dropupClassName = 'ui-datepicker-dialog-is-above';

/**
 * Combines space-separated line of events with widget's namespace
 *  for handling datepicker's position change
 *
 * @returns {string}
 * @private
 */
function getEvents(uuid) {
    let events = ['scroll', 'resize'];
    const ns = 'datepicker-' + uuid;

    events = $.map(events, function(eventName) {
        return eventName + '.' + ns;
    });

    return events.join(' ');
}

/**
 * Process position update for datepicker element
 */
function updatePos() {
    let pos;
    let isFixed;
    let offset;
    const input = this;
    const $input = $(this);

    const inst = $.datepicker._getInst(input);
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

    const isBelow = offset.top - $input.offset().top > 0;
    const isActualClass = $input.hasClass(dropdownClassName) === isBelow &&
        $input.hasClass(dropupClassName) !== isBelow;

    if (!isActualClass && inst.dpDiv.is(':visible') && $.datepicker._lastInput === input) {
        $input.toggleClass(dropdownClassName, isBelow);
        $input.toggleClass(dropupClassName, !isBelow);
        $input.trigger('datepicker:dialogReposition', isBelow ? 'below' : 'above');
    }
}

$.extend($.datepicker.constructor.prototype, {
    _connectDatepicker: function( target, inst ) {
        original._connectDatepicker.call(this, target, inst);
        inst.keepFocus = null;

        inst.dpDiv.off('keydown.prevent').on(
            'keydown.prevent', event => manageFocus.preventTabOutOfContainer(event, inst.dpDiv)
        );

        inst.dpDiv.off('keydown.datepicker').on('keydown.datepicker', this._onKeyboardNav.bind(this));
        inst.dpDiv.off('keyup.datepicker').on('keyup.datepicker', this._onKeyUpNav.bind(this));

        $.datepicker.uuid = this.uuid;
    },

    _attachHandlers(inst) {
        original._attachHandlers.call(this, inst);

        if (inst.keepFocus) {
            manageFocus.focusTabbable(inst.dpDiv, inst.dpDiv.find(inst.keepFocus));
        }
    },

    _doKeyDown(event) {
        const inst = $.datepicker._getInst( event.target );

        switch ( event.keyCode ) {
            case $.ui.keyCode.UP:
            case $.ui.keyCode.LEFT:
            case $.ui.keyCode.RIGHT:
            case $.ui.keyCode.DOWN:
                manageFocus.focusTabbable(inst.dpDiv, inst.dpDiv.find('.ui-datepicker-calendar'));
                break;
            case $.ui.keyCode.ESCAPE:
                $(event.target).trigger('focus');
                break;
            case $.ui.keyCode.SPACE:
            case $.ui.keyCode.ENTER:
                if ($.datepicker._datepickerShowing) {
                    $(event.target).trigger('focus');
                } else {
                    $.datepicker._showDatepicker(event.target);
                    return false;
                }
                break;
        }

        original._doKeyDown.call(this, event);
    },

    _onKeyboardNav(event) {
        const curInst = this._curInst;
        const target = curInst.input;
        let handled = false;
        let focuseble = false;

        this._datepickerShowing = false;
        if (
            (event.keyCode === $.ui.keyCode.SPACE || event.keyCode === $.ui.keyCode.ENTER) &&
            $(event.target).attr('data-event') === 'click'
        ) {
            curInst.keepFocus = `[data-handler="${$(event.target).attr('data-handler')}"]`;
            $(event.target).trigger('click');
            event.preventDefault();
            return;
        }

        switch ( event.keyCode ) {
            case $.ui.keyCode.LEFT:
                this._adjustDate(target, -1, 'D');
                handled = $.ui.keyCode.LEFT;
                focuseble = true;
                break;
            case $.ui.keyCode.UP:
                this._adjustDate(target, -7, 'D');
                handled = $.ui.keyCode.UP;
                focuseble = true;
                break;
            case $.ui.keyCode.RIGHT:
                this._adjustDate(target, +1, 'D');
                handled = $.ui.keyCode.RIGHT;
                focuseble = true;
                break;
            case $.ui.keyCode.DOWN:
                this._adjustDate(target, +7, 'D');
                handled = $.ui.keyCode.DOWN;
                focuseble = true;
                break;
            case $.ui.keyCode.SPACE:
                event.target = target.get(0);
                event.keyCode = $.ui.keyCode.ENTER;
                $.datepicker._datepickerShowing = true;
                this._doKeyDown(event);
                handled = $.ui.keyCode.SPACE;
                break;
            case $.ui.keyCode.ENTER:
                event.target = target.get(0);
                $.datepicker._datepickerShowing = true;
                this._doKeyDown(event);
                handled = $.ui.keyCode.ENTER;
                break;
            case $.ui.keyCode.TAB:
                break;
            default:
                event.target = target.get(0);
                $.datepicker._datepickerShowing = true;
                this._doKeyDown(event);
                return;
        }

        if (handled) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (focuseble) {
            this.focusCell(curInst);
        }

        $.datepicker._datepickerShowing = false;
    },

    _onKeyUpNav(event) {
        const curInst = this._curInst;
        this._datepickerShowing = true;
        if (
            (event.keyCode === $.ui.keyCode.SPACE || event.keyCode === $.ui.keyCode.ENTER) &&
            $(event.target).attr('data-event') === 'click'
        ) {
            manageFocus.focusTabbable(curInst.dpDiv, $(event.target));
            curInst.keepFocus = null;
        }
    },

    focusCell(inst) {
        const {selectedDay, selectedMonth, selectedYear} = inst;
        const activeCell = inst.dpDiv
            .find(`[data-year="${selectedYear}"][data-month="${selectedMonth}"]`)
            .filter((index, cell) => $(cell).find('a').text().trim() === selectedDay.toString());

        inst.dpDiv.find('.ui-datepicker-calendar').attr({
            'aria-activedescendant': activeCell.find('a').attr('id')
        });

        manageFocus.focusTabbable(inst.dpDiv, inst.dpDiv.find('.ui-datepicker-calendar'));

        inst.dpDiv.find('.ui-datepicker-days-cell-over').removeClass('ui-datepicker-days-cell-over');
        activeCell.addClass('ui-datepicker-days-cell-over');

        inst.dpDiv.find('.ui-datepicker-calendar').find('.ui-state-hover').removeClass('ui-state-hover');
        activeCell.find('a').addClass('ui-state-hover');
    },

    _generateHTML(inst) {
        const $html = $('<div />').append(original._generateHTML.call(this, inst));

        $html.find('.ui-datepicker-title').attr({
            'id': `ui-datepicker-title-${this.uuid}`,
            'aria-atomic': true,
            'aria-live': 'assertive'
        });

        $html.find('[data-handler="prev"]').attr({
            tabindex: 0,
            role: 'button'
        });

        $html.find('[data-handler="next"]').attr({
            tabindex: 0,
            role: 'button'
        });

        $html.find('.ui-state-disabled').attr('aria-disabled', 'true');

        $html.find('.ui-datepicker-calendar').attr({
            'tabindex': 0,
            'role': 'grid',
            'aria-readonly': true,
            'aria-activedescendant': ''
        });

        $html.find('td').each((index, td) => {
            const $td = $(td);
            const year = $td.data('year');
            const month = $td.data('month');
            const date = $td.find('a').text().trim();

            const dayDate = moment().set({
                year,
                month,
                date
            });

            $td.attr({
                role: 'gridcell'
            });

            $td.find('a').attr({
                'id': `ui-datepicker-${month}-${date}`,
                'aria-label': dayDate.format(this._defaults.weekDay),
                'tabindex': '-1',
                'aria-selected': $td.find('a').hasClass('ui-state-active')
            });

            if ($td.hasClass('ui-datepicker-today')) {
                $td.find('a').attr({
                    'aria-current': 'date'
                });
            }
        });

        return $html.html();
    },

    _attachments($input, inst) {
        $input
            .off('click', this._showDatepicker)
            .click(this._showDatepicker);
        original._attachments.call(this, $input, inst);
    },

    /**
     * Bind update position method after datepicker is opened
     *
     * @param elem
     * @override
     * @private
     */
    _showDatepicker(elem, ...rest) {
        original._showDatepicker.call(this, elem, ...rest);

        const input = elem.target || elem;
        const $input = $(input);

        const events = getEvents($input.id);

        const inst = $.datepicker._getInst(input);

        // set bigger zIndex difference between dropdown and input, to have place for dropdown mask
        inst.dpDiv.css('z-index', Number(inst.dpDiv.css('z-index')) + 2);
        inst.dpDiv.attr({
            'role': 'dialog',
            'aria-modal': 'true',
            'aria-labelledby': `ui-datepicker-title-${$.datepicker.uuid}`
        });

        $input
            .removeClass(dropdownClassName + ' ' + dropupClassName)
            .parents().add(window).each(function() {
                $(this).on(events, updatePos.bind(input));
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
    },

    /**
     * Remove all handlers before closing datepicker
     *
     * @param elem
     * @override
     * @private
     */
    _hideDatepicker(elem, ...rest) {
        let input = elem;
        const dpDiv = $.datepicker._curInst.dpDiv;

        if (!elem) {
            if (!$.datepicker._curInst) {
                return;
            }
            input = $.datepicker._curInst.input.get(0);
        }
        const events = getEvents(input.id);

        const $input = $(input);
        $input
            .removeClass(dropdownClassName + ' ' + dropupClassName)
            .parents().add(window).each(function() {
                $(this).off(events);
            });

        dpDiv.trigger('content:remove', dpDiv);

        original._hideDatepicker.call(this, elem, ...rest);

        $input.trigger('datepicker:dialogHide');
    },

    _updateDatepicker(inst) {
        original._updateDatepicker.call(this, inst);

        inst.dpDiv.trigger('content:changed', inst.dpDiv);
    },

    _destroyDatepicker(...args) {
        if (!this._curInst) {
            return;
        }
        if (this._curInst.input) {
            this._curInst.input.datepicker('hide')
                .off('click', this._showDatepicker);
        }
        original._destroyDatepicker.apply(this, args);
    }
});
