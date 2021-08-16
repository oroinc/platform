define(function(require) {
    'use strict';

    const $ = require('jquery');
    const TableCellIterator = require('orodatagrid/js/datagrid/table-cell-iterator').default;
    const mockGid = `
        <div role="grid">
            <div aria-rowindex="1">
                <div aria-colindex="1">title 1</div>
                <div aria-colindex="2">title 2</div>
                <div aria-colindex="3">title 3</div>
            </div>
            <div aria-rowindex="2">
                <div aria-colindex="1">1</div>
                <div aria-colindex="2">2</div>
                <div aria-colindex="3">3</div>
            </div>
            <div aria-rowindex="3">
                <div aria-colindex="1">4</div>
                <div aria-colindex="2">5</div>
                <div aria-colindex="3">6</div>
            </div>
        </div>`;

    describe('orodatagrid/js/datagrid/table-cell-iterator', function() {
        let tableCellIterator;
        let changeCurrentHandler;

        beforeEach(function() {
            tableCellIterator = new TableCellIterator($(mockGid));
            changeCurrentHandler = jasmine.createSpy('changeCurrentHandler');
            tableCellIterator.on('change:current', changeCurrentHandler);
        });

        it('check constructor required arguments', function() {
            expect(() => new TableCellIterator()).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator([])).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator({})).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator($())).toThrowError('Option "$table" is required');
            expect(() => new TableCellIterator($(''))).toThrowError('Option "$table" is required');

            expect(() => new TableCellIterator($(mockGid))).not.toThrowError('Option "$table" is required');
        });

        it('check initialized state', function() {
            expect(tableCellIterator.$cell instanceof $).toBe(true);
            expect(tableCellIterator.$row instanceof $).toBe(true);
            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
        });

        it('check "setCurrentCell" method', function() {
            const $cell = tableCellIterator.$row.find('[aria-colindex]:last');

            tableCellIterator.setCurrentCell($cell);

            expect(tableCellIterator.$cell).toEqual($cell);
            expect(changeCurrentHandler).toHaveBeenCalled();
        });

        it('check "setCurrentCell" method with invalid arguments', function() {
            tableCellIterator.setCurrentCell($('<div></div>'));
            tableCellIterator.setCurrentCell($(''));
            tableCellIterator.setCurrentCell($(null));
            tableCellIterator.setCurrentCell($(void 0));

            expect(changeCurrentHandler).not.toHaveBeenCalled();
        });

        it('check "next" method', function() {
            let $previousCell = tableCellIterator.$cell;
            tableCellIterator.next();

            expect(tableCellIterator.colindex).toBe(2);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            $previousCell = tableCellIterator.$cell;
            tableCellIterator.next();

            expect(tableCellIterator.colindex).toBe(3);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            tableCellIterator.next();

            expect(tableCellIterator.colindex).toBe(3);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler.calls.count()).toEqual(2);
        });

        it('check "prev" method', function() {
            tableCellIterator.prev();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler.calls.count()).toEqual(0);

            tableCellIterator.next();
            tableCellIterator.next();

            expect(tableCellIterator.colindex).toBe(3);
            expect(tableCellIterator.rowindex).toBe(1);

            let $previousCell = tableCellIterator.$cell;

            tableCellIterator.prev();
            expect(tableCellIterator.colindex).toBe(2);
            expect(tableCellIterator.rowindex).toBe(1);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            $previousCell = tableCellIterator.$cell;

            tableCellIterator.prev();
            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);
        });

        it('check "firstInRow" method', function() {
            tableCellIterator.firstInRow();
            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.next();
            tableCellIterator.next();

            tableCellIterator.firstInRow();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
        });

        it('check "lastInRow" method', function() {
            const $previousCell = tableCellIterator.$cell;

            tableCellIterator.lastInRow();
            expect(tableCellIterator.colindex).toBe(3);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);

            tableCellIterator.lastInRow();
            expect(tableCellIterator.colindex).toBe(3);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler.calls.count()).toEqual(1);
        });

        it('check "prevRow" method', function() {
            tableCellIterator.prevRow();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.nextRow();
            tableCellIterator.nextRow();

            tableCellIterator.prevRow();
            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(2);
        });

        it('check "nextRow" method', function() {
            tableCellIterator.nextRow();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(2);
            expect(changeCurrentHandler).toHaveBeenCalled();

            tableCellIterator.nextRow();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(3);
            expect(changeCurrentHandler).toHaveBeenCalled();
            expect(changeCurrentHandler.calls.count()).toEqual(2);
        });

        it('check "firstRow" method', function() {
            tableCellIterator.firstRow();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);
            expect(changeCurrentHandler).not.toHaveBeenCalled();

            tableCellIterator.nextRow();
            tableCellIterator.nextRow();

            const $previousCell = tableCellIterator.$cell;

            tableCellIterator.firstRow();

            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(1);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);
        });

        it('check "lastRow" method', function() {
            const $previousCell = tableCellIterator.$cell;

            tableCellIterator.lastRow();
            expect(tableCellIterator.colindex).toBe(1);
            expect(tableCellIterator.rowindex).toBe(3);

            expect(changeCurrentHandler.calls.mostRecent().args).toEqual([
                tableCellIterator.$cell,
                $previousCell
            ]);
        });
    });
});
