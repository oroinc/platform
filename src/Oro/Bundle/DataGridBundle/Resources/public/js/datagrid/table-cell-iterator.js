import {Events} from 'backbone';

class TableCellIterator {
    /**
     * @param {jQuery} $table
     */
    constructor($table) {
        if ($table === void 0 || !($table[0] instanceof HTMLElement)) {
            throw new Error('Option "$table" is required');
        }

        this.$table = $table;
        this.setCurrentCell($table.find('[aria-colindex]:first'));
    }

    setCurrentCell($cell) {
        // Set a new cell only if it is a child of current iterable table
        if (!this.$table[0].contains($cell[0]) || !$cell.is('[aria-colindex]') || $cell.is(this._$cell)) {
            return this;
        }

        const {_$cell} = this;
        this._$cell = $cell;
        this.trigger('change:current', $cell, _$cell);
        return this;
    }

    get $cell() {
        return this._$cell;
    }

    get $row() {
        return this.$cell.closest('[aria-rowindex]');
    }

    get colindex() {
        return Number(this.$cell.attr('aria-colindex'));
    }

    get rowindex() {
        return Number(this.$row.attr('aria-rowindex'));
    }

    get index() {
        return [this.rowindex, this.colindex];
    }

    prev() {
        const $cell = this.$cell.prevAll('[aria-colindex]:visible:first');
        if ($cell.length) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    next() {
        const $cell = this.$cell.nextAll('[aria-colindex]:visible:first');
        if ($cell.length) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    firstInRow() {
        const $cell = this.$row.find('[aria-colindex]:visible:first');
        if (!this.$cell.is($cell)) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    lastInRow() {
        const $cell = this.$row.find('[aria-colindex]:visible:last');
        if (!this.$cell.is($cell)) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    prevRow() {
        return this._goToRow(-1);
    }

    nextRow() {
        return this._goToRow(1);
    }

    firstRow() {
        return this._goToRow(-Infinity);
    }

    lastRow() {
        return this._goToRow(Infinity);
    }

    _goToRow(step) {
        const $rows = this.$table.find('[aria-rowindex]:visible');
        const place = $rows.index(this.$row);
        let goTo;
        let $cell;

        if (step === -Infinity) {
            // go to first row
            goTo = 0;
            step = 1; // start from first row and increase rowindex number
        } else if (step === Infinity) {
            // go to last row
            goTo = $rows.length - 1;
            step = -1; // start from last row and decrease rowindex number
        } else {
            // go to row with increment
            goTo = place + step;
        }

        while (
            goTo !== place &&
            goTo >= 0 &&
            goTo < $rows.length &&
            !$cell
        ) {
            $cell = $rows.eq(goTo).find(`[aria-colindex="${this.colindex}"]:visible:first`);
            if (!$cell.length) {
                goTo += step;
                $cell = null;
            }
        }

        if ($cell) {
            this.setCurrentCell($cell);
        }

        return this;
    }
}

Object.assign(TableCellIterator.prototype, Events);

export default TableCellIterator;
