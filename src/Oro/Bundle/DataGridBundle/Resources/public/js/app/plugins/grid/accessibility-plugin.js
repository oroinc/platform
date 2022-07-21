import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import TableCellIterator from 'orodatagrid/js/datagrid/table-cell-iterator';
import manageFocus from 'oroui/js/tools/manage-focus';
import _ from 'underscore';
import $ from 'jquery';

function getVisibleText($elem) {
    return $elem.clone().find('.sr-only').remove().end().text().trim();
}

const AccessibilityPlugin = BasePlugin.extend({
    /**
     * @type {TableCellIterator}
     */
    iterator: null,

    events() {
        return {
            'select2-close table.grid-main-container'() {
                this._forceInnerFocus = true;
            },
            'focusin table.grid-main-container': 'onFocusin',
            'focusout table.grid-main-container': 'onFocusout',
            'keydown table.grid-main-container': 'onKeyDown',
            'keyup table.grid-main-container': 'onKeyUp'
        };
    },

    /**
     * Selector for elements that have to be ignores during Entrusted element selection
     * @type {string}
     */
    ignoreEntrusted: '[data-toggle]',

    /**
     * Flag, says that an action happens during mouse click
     * @type {boolean}
     */
    _isDocumentClick: false,

    /**
     * Key that has been pressed on a page, or undefined
     * @type {string}
     */
    _isDocumentKeyPressed: void 0,

    /**
     * Flag, says that focus is on inner element of current cell and it is not entrusted element
     * @type {boolean}
     */
    _isFocusInside: false,

    /**
     * Flag, says that focus should be allowed on inner element of a cell,
     * even though related target is outside of the grid table
     * @type {boolean}
     */
    _forceInnerFocus: false,

    /**
     * Index of last focused cell
     * @type {[number, number], null}
     */
    _lastIndex: null,

    constructor: function AccessibilityPlugin(main, options) {
        AccessibilityPlugin.__super__.constructor.call(this, main, options);
    },

    initialize(main, options) {
        Object.assign(this, _.pick(options, 'ignoreEntrusted'));

        this.listenTo(this.main, {
            'content:update'() {
                if (this.enabled && !this.suspended && this.$table.is(':visible')) {
                    this._resumeNavigation();
                }
            },
            'loading-mask:show': this._suspendNavigation,
            'loading-mask:hide': this._resumeNavigation
        });

        this.listenToOnce(this.main, 'rendered', () => {
            this.$table = this.main.$('table.grid-main-container');
            // enable plugin only when the grid table is rendered
            this.enable();
        });
        AccessibilityPlugin.__super__.initialize.call(this, main, options);
    },

    delegateEvents() {
        AccessibilityPlugin.__super__.delegateEvents.call(this);
        $(document).on({
            [`mousedown${this.ownEventNamespace()}`]: this.onDocumentMouseDown.bind(this),
            [`mouseup${this.ownEventNamespace()}`]: this.onDocumentMouseUp.bind(this),
            [`dragstart${this.ownEventNamespace()}`]: this.onDocumentDragstart.bind(this),
            [`keydown${this.ownEventNamespace()}`]: this.onDocumentKeyDown.bind(this),
            [`keyup${this.ownEventNamespace()}`]: this.onDocumentKeyUp.bind(this)
        });
    },

    undelegateEvents() {
        AccessibilityPlugin.__super__.undelegateEvents.call(this);
        $(document).off(this.ownEventNamespace());
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.disable();

        AccessibilityPlugin.__super__.dispose.call(this);
    },

    enable() {
        if (this.$table === void 0 || this.$table.length === 0) {
            // can not be enabled without table
            return;
        }

        this.iterator = new TableCellIterator(this.$table);
        this.listenTo(this.iterator, 'change:current', this.onCurrentChange);

        this._resetCurrent();

        this.delegateEvents();

        AccessibilityPlugin.__super__.enable.call(this);
    },

    disable() {
        if (!this.enabled) {
            return;
        }

        if (this.$table) {
            this.$table.find('[aria-colindex]')
                .removeAttr('tabindex')
                .removeAttr('data-ignore-tabbable');
        }
        this.undelegateEvents();
        this.stopListening(this.iterator);
        delete this.iterator;

        // do not call parent disable method, it stops listeners and the plugin what be auto-enabled again
        this.enabled = false;
        this.trigger('disabled');
    },

    onFocusin(e) {
        clearTimeout(this._focusoutTimeoutID);

        const $targetElem = this.$table.find(e.target);
        const $targetCell = $targetElem.closest('[aria-colindex]');
        const allowInnerFocus =
            this._isDocumentClick ||
            ( // focus is not been inside and it is received not by pressing Tab key
                this._isFocusInside ||
                this._isDocumentKeyPressed !== 'Tab'
            ) && ( // focus changed by program or it's inner focus movement
                !e.relatedTarget ||
                this.$table[0].contains(e.relatedTarget)
            ) && (
                // focus already inside cell or focus set on mouseover
                !$targetCell.is('[data-ignore-tabbable]') ||
                this._isFocusInside ||
                this._forceInnerFocus
            );

        let $newCell;

        if ($targetCell.is($targetElem) || allowInnerFocus) {
            $newCell = $targetCell;

            this._isFocusInside =
                !$targetCell.is($targetElem) &&
                !this.entrustedTabbable($targetCell).is($targetElem);
        }

        if ($newCell) {
            this.iterator.setCurrentCell($newCell);
        } else if (!$targetCell.is('[data-ignore-tabbable]')) {
            const $elem = this.defineFocusElement({allowInnerFocus});
            this.focusElement($elem);
        }

        if (!this.isInnerFocus() && $targetElem.is(document.activeElement)) {
            e.target.classList.add('focus-via-arrows-keys');
        }

        // single use for the flag
        this._forceInnerFocus = false;
        delete this._lastIndex;
    },

    onFocusout(e) {
        if (!e.relatedTarget || !this.$table[0].contains(e.relatedTarget)) {
            this._focusoutTimeoutID = setTimeout(() => {
                if (this.disposed) {
                    return;
                }
                // it might be a sequence of elemA.blur() and elemB.focus() custom events
                // preserve the flag value for some time,
                // in case the focus will be returned back to the table shortly
                this._isFocusInside = false;
                this.removeIgnoreTabbableAttributes();
            }, 10);

            if (!e.relatedTarget && $(document.activeElement).is('body')) {
                // body element has received focus without defined related target,
                // it means non specific focus target was established
                const $target = $(e.target);
                const $dropdownMenu = $target.closest('.dropdown-menu');
                if ($dropdownMenu.length) {
                    // it might be dropdown-menu close, therefore move focus back to dropdown toggler button
                    const $toggler = $dropdownMenu.parent().find('[data-toggle="dropdown"]');
                    this.focusElement($toggler.is(':tabbable') ? $toggler : $toggler.find(':tabbable:first'));
                } else if (!this.suspended && !this._isDocumentClick) {
                    // cell content might been deleting, therefore schedule a restore for the current cell
                    this._lastIndex = this.iterator.index;
                    setTimeout(() => {
                        this._restoreCurrent();
                    });
                }
            }
        }

        const $dialog = $(e.relatedTarget).closest('[role="dialog"], [role="alertdialog"]');
        if ($dialog.length) {
            // a dialog has received focus, subscribe on dialog close to set focus back to current cell
            this._lastIndex = this.iterator.index;
            if ($dialog.is('.ui-dialog')) {
                $dialog.one('dialogclose', this.onDialogClose.bind(this));
            } else {
                $dialog.one('hidden.bs.modal', this.onDialogClose.bind(this));
            }
        }

        e.target.classList.remove('focus-via-arrows-keys');
    },

    onKeyDown(e) {
        const $target = this.$table.find(e.target);
        const {$cell} = this.iterator;

        if (this.isInnerFocus()) {
            switch (e.key) {
                case 'Escape':
                    this._isFocusInside = false;
                    this.focusElement($cell);
                    e.preventDefault();
                    break;
                default:
                    manageFocus.preventTabOutOfContainer(e, $cell);
            }

            return;
        }

        switch (e.key) {
            case 'Tab':
                this.addIgnoreTabbableAttributes();
                break;
            case 'ArrowLeft':
                this.iterator.prev();
                e.preventDefault();
                break;
            case 'ArrowRight':
                this.iterator.next();
                e.preventDefault();
                break;
            case 'ArrowUp':
                this.iterator.prevRow();
                e.preventDefault();
                break;
            case 'ArrowDown':
                this.iterator.nextRow();
                e.preventDefault();
                break;
            case 'Home':
                if (e.ctrlKey) {
                    this.iterator.firstRow();
                }
                this.iterator.firstInRow();
                e.preventDefault();
                break;
            case 'End':
                if (e.ctrlKey) {
                    this.iterator.lastRow();
                }
                this.iterator.lastInRow();
                e.preventDefault();
                break;
            case 'PageDown':
                e.preventDefault();
                if (!this.main.disposed && this.main.collection.hasNext()) {
                    this.main.collection.getNextPage();
                }
                break;
            case 'PageUp':
                e.preventDefault();
                if (!this.main.disposed && this.main.collection.hasPrevious()) {
                    this.main.collection.getPreviousPage();
                }
                break;
            case 'Enter':
            case ' ':
                const $entrusted = this.entrustedTabbable($cell);
                const $tabbable = $cell.find(':tabbable');
                if ($target.is('[aria-colindex]') && !$entrusted.length) {
                    if ($tabbable.length) {
                        // target is a cell with several tabbable elements -- focus goes inside a cell
                        this._isFocusInside = true;
                        this.focusElement($tabbable.first());
                    } else {
                        // treat Enter/Space press as click to trigger row action
                        // trigger sequence of `mousedown`-`mouseup` events,
                        // to pass the check for `clickPermit` in GridRow
                        $target.trigger('mousedown');
                        $target.trigger('mouseup');
                        $target.click();
                    }
                    e.preventDefault();
                }
                break;
        }
    },

    onKeyUp(e) {
        if (e.key === 'Tab') {
            this.removeIgnoreTabbableAttributes();
        }
    },

    onCurrentChange() {
        this._updateTabindexAttribute();

        if (this.$table[0].contains(document.activeElement)) {
            const $elem = this.defineFocusElement({allowInnerFocus: true});
            this.focusElement($elem);
        }
    },

    addIgnoreTabbableAttributes() {
        this.$table.find('[aria-colindex]')
            .attr('data-ignore-tabbable', '');
    },

    removeIgnoreTabbableAttributes() {
        this.$table.find('[aria-colindex]')
            .removeAttr('data-ignore-tabbable');
    },

    defineFocusElement({allowInnerFocus = false}) {
        const $entrusted = this.entrustedTabbable(this.iterator.$cell);
        if (
            allowInnerFocus &&
            this.iterator.$cell[0].contains(document.activeElement) &&
            !$entrusted.length
        ) {
            return $(document.activeElement);
        } else if ($entrusted.length) {
            // focus element inside the cell or the cell itself
            return $entrusted;
        }

        return this.iterator.$cell;
    },

    focusElement($elem) {
        const {$cell} = this.iterator;

        if (!$cell.is($elem)) {
            $cell.removeAttr('data-ignore-tabbable');
        }

        if (!$elem.is(document.activeElement)) {
            $elem.focus();
        }
    },

    /**
     * Check it the $cell contains entrusted tabbable element to proxy focus to
     * @param $cell
     * @return {jQuery}
     */
    entrustedTabbable($cell) {
        const $tabbable = $cell.find(':tabbable');

        if (
            $tabbable.length === 1 &&
            $tabbable.is(`:not(${this.ignoreEntrusted})`) &&
            getVisibleText($tabbable) === getVisibleText($cell)
        ) {
            return $tabbable;
        }

        return $();
    },

    /**
     * Check whether focused element is not a Cell and not an Entrusted element within a Cell
     * @return {boolean}
     */
    isInnerFocus() {
        const {$cell} = this.iterator;
        return (
            this._isFocusInside &&
            !$cell.is(document.activeElement) &&
            !this.entrustedTabbable($cell).is(document.activeElement)
        );
    },

    onDialogClose() {
        this._restoreCurrent();
    },

    onDocumentMouseDown() {
        this._isDocumentClick = true;
    },

    onDocumentMouseUp() {
        this._isDocumentClick = false;
    },

    onDocumentDragstart() {
        this._isDocumentClick = false;
    },

    onDocumentKeyDown(e) {
        this._isDocumentKeyPressed = e.key;
    },

    onDocumentKeyUp() {
        delete this._isDocumentKeyPressed;
    },

    _updateTabindexAttribute() {
        const {$cell} = this.iterator;

        if (this.entrustedTabbable($cell).length) {
            $cell.removeAttr('tabindex');
        } else {
            $cell.attr('tabindex', 0);
        }

        this.$table.find('[aria-colindex]')
            .not($cell)
            .attr('tabindex', -1);
    },

    _resetCurrent() {
        const current = this.iterator;
        this.iterator.setCurrentCell(this.$table.find('[aria-colindex]:visible:first'));

        if (this.$table.find('[aria-colindex]:not(th)').length) {
            while (
                current.$cell.is('th') &&
                current.$cell !== current.nextRow().$cell
            ) {
                // do nothing, the iteration is done in the condition
            }
        }
        if (current.$cell.is('.select-row-cell')) {
            current.next();
        }
        if (current.$cell.is('.action-cell')) {
            current.prev();
        }
    },

    _restoreCurrent() {
        if (!this._lastIndex) {
            return;
        }

        let $cell;
        let [row, col] = this._lastIndex;
        delete this._lastIndex;

        while ((!$cell || !$cell.length) && row >= 0) {
            // try to find closest available cell
            $cell = this.$table.find(`[aria-rowindex="${row}"] [aria-colindex="${col}"]`);
            row -= 1;
        }

        if ($cell && $cell.length) {
            this.iterator.setCurrentCell($cell);

            if ($(document.activeElement).is('body')) {
                // body element is focused,
                // it means non specific focus target was established,
                // therefore restore focus on current cell
                const $elem = this.defineFocusElement({});
                this.focusElement($elem);
            }
        }
    },

    _suspendNavigation() {
        this.suspended = true;
        if (this.$table[0].contains(document.activeElement)) {
            this._lastIndex = this.iterator.index;
            document.activeElement.blur();
        }
    },

    _resumeNavigation() {
        this.suspended = false;

        if (!this.enabled) {
            return;
        }

        if (this._lastIndex) {
            this._restoreCurrent();
        } else if (!this.$table.find(this.iterator.$cell).is(':visible')) {
            // in case current cell is not available
            this._resetCurrent();
        }
        this._updateTabindexAttribute();
    }
});

export default AccessibilityPlugin;
