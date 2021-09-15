import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import TableCellIterator from 'orodatagrid/js/datagrid/table-cell-iterator';
import manageFocus from 'oroui/js/tools/manage-focus';
import _ from 'underscore';
import $ from 'jquery';

const AccessibilityPlugin = BasePlugin.extend({
    /**
     * @type {TableCellIterator}
     */
    iterator: null,

    events() {
        return {
            'select2-close table.grid'() {
                this._forceInnerFocus = true;
            },
            'focusin table.grid': 'onFocusin',
            'focusout table.grid': 'onFocusout',
            'keydown table.grid': 'onKeyDown'
        };
    },

    /**
     * Selector for elements that have to be ignores during Entrusted element selection
     * @type {string}
     */
    ignoreEntrusted: '[data-toggle], [data-trigger]',

    /**
     * Flag, says that an action happens during mouse click
     * @type {boolean}
     */
    _isDocumentClick: false,

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

    constructor: function AccessibilityPlugin(main, options) {
        AccessibilityPlugin.__super__.constructor.call(this, main, options);
    },

    initialize(main, options) {
        Object.assign(this, _.pick(options, 'ignoreEntrusted'));

        this.listenTo(this.main, {
            'content:update'() {
                if (!this.enabled) {
                    return;
                }
                if (!this.$table.find(this.iterator.$cell).length) {
                    // in case current cell is not available
                    this._resetCurrent();
                }
                this._updateTabindexAttribute();
            }
        });

        this.listenToOnce(this.main, 'rendered', () => {
            this.$table = this.main.$('table.grid');
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
            [`dragstart${this.ownEventNamespace()}`]: this.onDocumentDragstart.bind(this)
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
            this._isFocusInside ||
            this._isDocumentClick ||
            this._forceInnerFocus;

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

        if (!this.isInnerFocus()) {
            e.target.classList.add('focus-via-arrows-keys');
        }

        // single use for the flag
        this._forceInnerFocus = false;
    },

    onFocusout(e) {
        if (!e.relatedTarget || !$.contains(this.$table[0], e.relatedTarget)) {
            // focus goes out of the grid table
            this.$table.find('[aria-colindex]')
                .removeAttr('data-ignore-tabbable');

            this._focusoutTimeoutID = setTimeout(() => {
                // it might be a sequence of elemA.blur() and elemB.focus() custom events
                // preserve the flag value for some time,
                // in case the focus will be returned back to the table shortly
                this._isFocusInside = false;
            }, 10);
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
                if (
                    $target.is('[aria-colindex]') &&
                    !$entrusted.length &&
                    $tabbable.length
                ) {
                    // target is a cell with several tabbable elements -- focus goes inside a cell
                    this._isFocusInside = true;
                    this.focusElement($tabbable.first());
                    e.preventDefault();
                }
                break;
        }
    },

    onCurrentChange() {
        this._updateTabindexAttribute();

        if ($.contains(this.$table[0], document.activeElement)) {
            const $elem = this.defineFocusElement({allowInnerFocus: true});
            this.focusElement($elem);
        }
    },

    defineFocusElement({allowInnerFocus}) {
        const $entrusted = this.entrustedTabbable(this.iterator.$cell);
        if (
            allowInnerFocus &&
            $.contains(this.iterator.$cell[0], document.activeElement) &&
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

        this.$table.find('[aria-colindex]')
            .attr('data-ignore-tabbable', '');

        if (!$cell.is($elem)) {
            $cell.removeAttr('data-ignore-tabbable');
        }

        if (!$elem.is(document.activeElement)) {
            _.delay(() => {
                $elem.focus();
            });
        }
    },

    /**
     * Check it the $cell contains entrusted tabbable element to proxy focus to
     * @param $cell
     * @return {jQuery}
     */
    entrustedTabbable($cell) {
        const $tabbable = $cell.find(':tabbable');
        return $tabbable.length === 1 && $tabbable.is(`:not(${this.ignoreEntrusted})`) ? $tabbable : $();
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

    onDocumentMouseDown() {
        this._isDocumentClick = true;
    },

    onDocumentMouseUp() {
        this._isDocumentClick = false;
    },

    onDocumentDragstart() {
        this._isDocumentClick = false;
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
        this.iterator.setCurrentCell(this.$table.find('[aria-colindex]:first'));

        if (this.$table.find('[aria-colindex]:not(th)').length) {
            while (current.$cell.is('th')) {
                current.nextRow();
            }
        }
        if (current.$cell.is('.select-row-cell')) {
            current.next();
        }
        if (current.$cell.is('.action-cell')) {
            current.prev();
        }
    }
});

export default AccessibilityPlugin;
