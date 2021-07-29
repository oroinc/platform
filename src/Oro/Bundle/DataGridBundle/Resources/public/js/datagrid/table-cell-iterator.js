import {Events} from 'backbone';

class TableCellIterator {
    constructor($table) {
        this.$table = $table;
        this.setCurrentCell($table.find('[aria-colindex]:first'));
    }

    setCurrentCell($cell) {
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

    get isFirstInRow() {
        return this.$row.find('[aria-colindex]:first').is(this.$cell);
    }

    get isLastInRow() {
        return this.$row.find('[aria-colindex]:last').is(this.$cell);
    }

    get isFirstRow() {
        return this.$table.find('[aria-rowindex]:first').is(this.$row);
    }

    get isLastRow() {
        return this.$table.find('[aria-rowindex]:last').is(this.$row);
    }

    prev() {
        const $cell = this.$cell.prev('[aria-colindex]');
        if ($cell.length) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    next() {
        const $cell = this.$cell.next('[aria-colindex]');
        if ($cell.length) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    firstInRow() {
        const $cell = this.$row.find('[aria-colindex]:first');
        if (!this.$cell.is($cell)) {
            this.setCurrentCell($cell);
        }
        return this;
    }

    lastInRow() {
        const $cell = this.$row.find('[aria-colindex]:last');
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
        return this._goToRow(0);
    }

    lastRow() {
        return this._goToRow(Infinity);
    }

    _goToRow(step) {
        const $rows = this.$table.find('[aria-rowindex]');
        const place = $rows.index(this.$row);
        let goTo;

        if (step === 0) {
            // go to first row
            goTo = 0;
        } else if (step === Infinity) {
            // go to last row
            goTo = $rows.length - 1;
        } else {
            // go to row with increment
            goTo = place + step;
        }

        if (goTo !== place && goTo >= 0 && goTo < $rows.length) {
            this.setCurrentCell($rows.eq(goTo).find(`[aria-colindex="${this.colindex}"]:first`));
        }

        return this;
    }
}

Object.assign(TableCellIterator.prototype, Events);

export default TableCellIterator;
