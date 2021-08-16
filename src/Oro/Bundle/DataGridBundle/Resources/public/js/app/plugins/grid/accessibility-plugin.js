import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import TableCellIterator from 'orodatagrid/js/datagrid/table-cell-iterator';
import $ from 'jquery';

const delegateEventSplitter = /^(\S+)\s*(.*)$/;

const AccessibilityPlugin = BasePlugin.extend({
    /**
     * @type {TableCellIterator}
     */
    iterator: null,

    events: {
        'focusin table.grid': 'onFocusin',
        'focusout table.grid': 'onFocusout',
        'keydown table.grid': 'onKey'
    },

    constructor: function AccessibilityPlugin(main, options) {
        AccessibilityPlugin.__super__.constructor.call(this, main, options);
    },

    initialize(main, options) {
        this.listenTo(this.main, {
            'content:update'() {
                if (!this.enabled) {
                    return;
                }
                if (!this.$table.find(this.iterator.$cell).length) {
                    // in case current cell is not available
                    this._resetCurrent();
                }
                this._updateAttributes();
            },
            'grid-cell:enter-edit-mode'() {
                this.disable();
            },
            'grid-cell:exit-edit-mode'(cell) {
                this.enable();

                if (!cell.disposed) {
                    this.iterator.setCurrentCell(cell.$el);
                }
            },
            enable: this.enable,
            disable: this.disable
        });

        this.listenToOnce(this.main, 'rendered', () => {
            this.$table = this.main.$('table.grid');
            // enable plugin only when the grid table is rendered
            this.enable();
        });
        AccessibilityPlugin.__super__.initialize.call(this, main, options);
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
        this.listenTo(this.iterator, 'change:current', () => {
            if ($.contains(this.$table[0], document.activeElement)) {
                this.iterator.$cell.focus();
            }
            this._updateAttributes();
        });

        this._resetCurrent();

        this.delegateEvents();

        AccessibilityPlugin.__super__.enable.call(this);
    },

    disable() {
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

    delegateEvents() {
        const {events} = this;
        this.undelegateEvents();
        for (const [key, method] of Object.entries(events)) {
            const [, event, selector] = key.match(delegateEventSplitter);
            this.main.$el.on(`${event}${this.eventNamespace()}`, selector, this[method].bind(this));
        }
        return this;
    },

    undelegateEvents() {
        this.main.$el.off(this.ownEventNamespace());
        return this;
    },

    onFocusin(e) {
        // Focus is still be inside of a table
        if ($.contains(e.currentTarget, document.activeElement)) {
            return;
        }

        const $target = this.$table.find(e.target);
        // Make the cell in focus as current if it was not a current
        if ($target.is('[aria-colindex]') && !$target.is(this.iterator.$cell)) {
            this.iterator.setCurrentCell($target);
        }

        this.$table.find('[aria-colindex]')
            .not(this.iterator.$cell)
            .attr('data-ignore-tabbable', '');
    },

    onFocusout() {
        this.$table.find('[aria-colindex]')
            .not(this.iterator.$cell)
            .removeAttr('data-ignore-tabbable');
    },

    onKey(e) {
        const $target = this.$table.find(e.target);
        if (!$target.is('[aria-colindex]')) {
            // target is not a cell -- nothing to do
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
        }
    },

    _updateAttributes() {
        const {$cell} = this.iterator;

        // update attributes for current cell element
        $cell
            .attr('tabindex', 0)
            .removeAttr('data-ignore-tabbable');
        this.$table.find('[aria-colindex]')
            .not($cell)
            // to allow set focus over click on other cell elements
            .attr('tabindex', -1);
    },

    _resetCurrent() {
        const current = this.iterator;
        this.iterator.setCurrentCell(this.$table.find('[aria-colindex]:first'));

        if (this.$table.find('td[aria-colindex]').length) {
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
